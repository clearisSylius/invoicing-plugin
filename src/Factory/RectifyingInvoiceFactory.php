<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceSeriesRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Entity\Invoice;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceLineItem;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTaxItem;
use ClearisSylius\InvoicingPlugin\Generator\InvoiceNumberGeneratorInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceStateEnum;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use ClearisSylius\InvoicingPlugin\Model\RectificationReasonEnum;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * Build a rectifying invoice from an existing original.
 *
 * - Numbering: pulled from the RECTIFYING series of the channel (separate
 *   counter and format from STANDARD; this matches Spanish fiscal usage of
 *   prefix "R-").
 * - Snapshots: a fresh BillingData and ShopBillingData are cloned from the
 *   original so the rectifying invoice carries its own immutable copies.
 *   The original's snapshots remain untouched.
 * - Amounts: total rectification negates the original; partial replaces them
 *   with the supplied numbers (sign decided by the caller — typically
 *   negative for a refund/credit note, positive for a price correction).
 * - Lines / Tax: line items and tax items are NOT copied automatically for
 *   partial rectifications because the rectification scope is rarely the
 *   whole order. For total rectifications we replicate the originals with
 *   sign-flipped amounts so the libro registro stays consistent.
 *
 * The rectification also flips the original to state CANCELLED when it is a
 * total rectification — the workflow transition is the caller's job.
 */
final class RectifyingInvoiceFactory implements RectifyingInvoiceFactoryInterface
{
    public function __construct(
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly InvoiceSeriesRepositoryInterface $seriesRepository,
        private readonly InvoiceNumberGeneratorInterface $numberGenerator,
        private readonly BillingDataSnapshotter $billingDataSnapshotter,
        private readonly ShopBillingDataSnapshotter $shopBillingDataSnapshotter,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function create(
        InvoiceInterface $original,
        string $reason,
        bool $isTotal,
        array $partialAmounts = [],
    ): InvoiceInterface {
        if (!in_array($reason, RectificationReasonEnum::all(), true)) {
            throw new \InvalidArgumentException(sprintf('Unknown rectification reason "%s".', $reason));
        }

        $channel = $original->getChannel();
        if (!$channel instanceof ChannelInterface) {
            throw new \LogicException('Original invoice has no channel.');
        }

        $settings = $this->settingsRepository->findByChannel($channel);
        $series = $settings?->getRectifyingSeries()
            ?? $this->seriesRepository->findActiveByChannelAndType($channel, InvoiceTypeEnum::RECTIFYING);

        // Safety net: legacy installations (or fresh setups where the admin
        // only configured a standard series) won't have a rectifying series
        // yet. Auto-provision one on demand from the original invoice's
        // series — prefix the code with "R-" and the format too. This way
        // the first rectification "just works" and the admin can later edit
        // the auto-created series to taste.
        if ($series === null) {
            $series = $this->autoProvisionRectifyingSeries($channel, $original->getSeries());
        }

        // ShopBillingData: prefer the channel's current setting (regulations
        // require the rectifying invoice to carry the issuer's data AT THE
        // TIME OF RECTIFICATION, not at the time the original was issued).
        $shopBillingSource = $settings?->getShopBillingData() ?? $original->getShopBillingData();

        $billingAddress = $original->getOrder()->getBillingAddress();
        $billingData = $billingAddress !== null
            ? $this->billingDataSnapshotter->snapshot($billingAddress)
            // Defensive fallback: order was anonymised → clone from original.
            : (function () use ($original) {
                $copy = new \ClearisSylius\InvoicingPlugin\Entity\BillingData();
                $copy->initialise(
                    firstName: $original->getBillingData()->getFirstName(),
                    lastName: $original->getBillingData()->getLastName(),
                    company: $original->getBillingData()->getCompany(),
                    taxId: $original->getBillingData()->getTaxId(),
                    street: $original->getBillingData()->getStreet(),
                    city: $original->getBillingData()->getCity(),
                    postcode: $original->getBillingData()->getPostcode(),
                    provinceCode: $original->getBillingData()->getProvinceCode(),
                    provinceName: $original->getBillingData()->getProvinceName(),
                    countryCode: $original->getBillingData()->getCountryCode(),
                );

                return $copy;
            })();

        $issuedAt = new \DateTimeImmutable();
        $number = $this->numberGenerator->generate($series, $issuedAt);

        // Compute amounts.
        if ($isTotal) {
            $subtotal = -$original->getSubtotal();
            $taxesTotal = -$original->getTaxesTotal();
            $total = -$original->getTotal();
        } else {
            $subtotal = $partialAmounts['base'] ?? 0;
            $taxesTotal = $partialAmounts['taxes'] ?? 0;
            $total = $partialAmounts['total'] ?? ($subtotal + $taxesTotal);
        }

        $rectifying = new Invoice();
        $rectifying->initialise(
            type: InvoiceTypeEnum::RECTIFYING,
            number: $number,
            series: $series,
            order: $original->getOrder(),
            channel: $channel,
            currencyCode: $original->getCurrencyCode(),
            localeCode: $original->getLocaleCode(),
            billingData: $billingData,
            shopBillingData: $this->shopBillingDataSnapshotter->snapshot($shopBillingSource),
            subtotal: $subtotal,
            taxesTotal: $taxesTotal,
            total: $total,
            paymentState: $original->getPaymentState(),
            rectifiedInvoice: $original,
            rectificationReason: $reason,
            issuedAt: $issuedAt,
        );

        if ($isTotal) {
            // Mirror every line/tax with reversed sign so the libro registro
            // reconciles when summed across (original + rectifying).
            foreach ($original->getLineItems() as $orig) {
                $line = new InvoiceLineItem();
                $line->initialise(
                    name: $orig->getName(),
                    variantName: $orig->getVariantName(),
                    variantCode: $orig->getVariantCode(),
                    quantity: $orig->getQuantity(),
                    unitPrice: $orig->getUnitPrice(),
                    discountedUnitNetPrice: $orig->getDiscountedUnitNetPrice(),
                    subtotal: -$orig->getSubtotal(),
                    taxRate: $orig->getTaxRate(),
                    taxTotal: -$orig->getTaxTotal(),
                    total: -$orig->getTotal(),
                );
                $rectifying->addLineItem($line);
            }
            foreach ($original->getTaxItems() as $orig) {
                $tax = new InvoiceTaxItem();
                $tax->initialise(
                    label: $orig->getLabel(),
                    rate: $orig->getRate(),
                    base: -$orig->getBase(),
                    amount: -$orig->getAmount(),
                );
                $rectifying->addTaxItem($tax);
            }

            // The transition to CANCELLED on the original is the caller's
            // responsibility — see RectifyInvoiceHandler.
            $original->setState(InvoiceStateEnum::CANCELLED);
        }

        return $rectifying;
    }

    /**
     * Create + persist a rectifying InvoiceSeries derived from the standard
     * series used by the original invoice.
     *
     * Why we persist (rather than build in-memory): the InvoiceNumberGenerator
     * locks the row with `find(..., LockMode::PESSIMISTIC_WRITE)` which only
     * works on a managed entity. We flush immediately so subsequent calls
     * (`findActiveByChannelAndType`) inside the same transaction see it.
     */
    private function autoProvisionRectifyingSeries(
        ChannelInterface $channel,
        ?InvoiceSeriesInterface $standardSeries,
    ): InvoiceSeriesInterface {
        $baseCode = $standardSeries?->getCode() ?? 'STANDARD';
        $baseFormat = $standardSeries?->getFormat() ?? '{year}/{number}';
        $padding = $standardSeries?->getPadding() ?? 4;
        $yearlyReset = $standardSeries?->isYearlyReset() ?? true;

        // Defensive: if a series with the proposed code already exists from a
        // previous auto-provision but it's INACTIVE for some reason, just
        // reactivate it. The unique-constraint on `code` is the canonical
        // collision surface.
        $candidateCode = 'R-' . $baseCode;
        $existing = $this->seriesRepository->findOneByCode($candidateCode);
        if ($existing !== null) {
            if (!$existing->isActive()) {
                $existing->setActive(true);
                $this->entityManager->flush();
            }

            return $existing;
        }

        $rectifying = new InvoiceSeries();
        $rectifying->setCode($candidateCode);
        $rectifying->setName('Rectificativas — ' . ($standardSeries?->getName() ?? $baseCode));
        $rectifying->setChannel($channel);
        $rectifying->setType(InvoiceTypeEnum::RECTIFYING);
        // Prepend "R-" to the rendered number so it's unmistakably a rectifying
        // invoice on screen and in the libro registro (also satisfies the
        // RD 1619/2012 requirement that rectifying invoices have a distinct
        // series). We only mutate the format if it doesn't already start with
        // "R" — defensive against odd existing standard formats.
        $rectifying->setFormat(str_starts_with($baseFormat, 'R') ? $baseFormat : ('R-' . $baseFormat));
        $rectifying->setPadding($padding);
        $rectifying->setYearlyReset($yearlyReset);
        $rectifying->setActive(true);
        $rectifying->setCurrentNumber(0);

        $this->entityManager->persist($rectifying);
        $this->entityManager->flush();

        return $rectifying;
    }
}
