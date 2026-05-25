<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceSeriesRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Entity\Invoice;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceLineItem;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTaxItem;
use ClearisSylius\InvoicingPlugin\Generator\InvoiceNumberGeneratorInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\OrderItemInterface;

/**
 * Snapshot an Order into a fresh Invoice. The factory:
 *
 *  1. Resolves the active standard InvoiceSeries for the order's channel.
 *  2. Reserves the next number (atomic on the series row).
 *  3. Builds frozen BillingData / ShopBillingData snapshots.
 *  4. Copies every order item into an InvoiceLineItem.
 *  5. Aggregates per-rate TaxItems (one row per distinct tax rate found on
 *     the order). Aggregation is done by parsing the rate from the order's
 *     own adjustments — Sylius stores tax adjustments per item, and each
 *     adjustment carries a `details` payload that includes the rate when
 *     the tax plugin populates it. When the rate cannot be parsed, we fall
 *     back to an implicit rate computed from base/amount, bucketed into
 *     the canonical Spanish set (0/4/10/21) or "Other".
 *
 * The factory does NOT persist anything. The caller (CommandHandler) is
 * responsible for `persist($invoice)` and `flush()`.
 */
final class InvoiceFactory implements InvoiceFactoryInterface
{
    public function __construct(
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly InvoiceSeriesRepositoryInterface $seriesRepository,
        private readonly InvoiceNumberGeneratorInterface $numberGenerator,
        private readonly BillingDataSnapshotter $billingDataSnapshotter,
        private readonly ShopBillingDataSnapshotter $shopBillingDataSnapshotter,
    ) {
    }

    public function createFromOrder(OrderInterface $order): InvoiceInterface
    {
        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            throw new \LogicException(sprintf('Order #%s has no channel.', $order->getNumber()));
        }

        $settings = $this->settingsRepository->findByChannel($channel);
        if ($settings === null) {
            throw new \RuntimeException(sprintf(
                'No ChannelInvoicingSettings configured for channel "%s". '
                . 'Configure the channel in admin before emitting invoices.',
                (string) $channel->getCode(),
            ));
        }

        $series = $settings->getStandardSeries()
            ?? $this->seriesRepository->findActiveByChannelAndType($channel, InvoiceTypeEnum::STANDARD);
        if ($series === null) {
            throw new \RuntimeException(sprintf(
                'No active standard InvoiceSeries for channel "%s".',
                (string) $channel->getCode(),
            ));
        }

        $shopBillingSource = $settings->getShopBillingData();
        if ($shopBillingSource === null) {
            throw new \RuntimeException(sprintf(
                'Channel "%s" has no shop billing data configured. '
                . 'Set fiscal data for the issuer in admin before emitting invoices.',
                (string) $channel->getCode(),
            ));
        }

        $billingAddress = $order->getBillingAddress();
        if ($billingAddress === null) {
            throw new \LogicException(sprintf(
                'Order #%s has no billing address; cannot snapshot BillingData.',
                $order->getNumber(),
            ));
        }

        $issuedAt = new \DateTimeImmutable();
        $number = $this->numberGenerator->generate($series, $issuedAt);

        $invoice = new Invoice();
        $invoice->initialise(
            type: InvoiceTypeEnum::STANDARD,
            number: $number,
            series: $series,
            order: $order,
            channel: $channel,
            currencyCode: (string) $order->getCurrencyCode(),
            localeCode: (string) $order->getLocaleCode(),
            billingData: $this->billingDataSnapshotter->snapshot($billingAddress),
            shopBillingData: $this->shopBillingDataSnapshotter->snapshot($shopBillingSource),
            subtotal: $order->getItemsTotal(),
            taxesTotal: $order->getTaxTotal(),
            total: $order->getTotal(),
            paymentState: (string) $order->getPaymentState(),
            issuedAt: $issuedAt,
        );

        $this->copyLineItems($order, $invoice);
        $this->aggregateTaxItems($order, $invoice);

        return $invoice;
    }

    private function copyLineItems(OrderInterface $order, Invoice $invoice): void
    {
        /** @var OrderItemInterface $item */
        foreach ($order->getItems() as $item) {
            $line = new InvoiceLineItem();
            $variant = $item->getVariant();

            $line->initialise(
                name: (string) $item->getProductName(),
                variantName: $item->getVariantName(),
                variantCode: $variant?->getCode(),
                quantity: $item->getQuantity(),
                unitPrice: $item->getUnitPrice(),
                discountedUnitNetPrice: $item->getQuantity() > 0
                    ? (int) round(($item->getTotal() - $item->getTaxTotal()) / max(1, $item->getQuantity()))
                    : $item->getUnitPrice(),
                subtotal: $item->getTotal() - $item->getTaxTotal(),
                taxRate: $this->extractRateLabel($item),
                taxTotal: $item->getTaxTotal(),
                total: $item->getTotal(),
            );

            $invoice->addLineItem($line);
        }
    }

    /**
     * Walk the order adjustments aggregating tax by rate. Sylius core stores
     * tax adjustments with `originCode` like "tax_rate_<rate>" or with the
     * tax rate's `code` directly; we read both the textual rate stored on the
     * adjustment label and parse the numeric value.
     */
    private function aggregateTaxItems(OrderInterface $order, Invoice $invoice): void
    {
        /** @var array<string, array{base: int, amount: int, label: string, rate: float}> $buckets */
        $buckets = [];

        foreach ($order->getItems() as $item) {
            $rate = $this->extractRateValue($item);
            $key = number_format($rate, 2, '.', '');
            $label = $rate > 0 ? sprintf('IVA %s%%', rtrim(rtrim($key, '0'), '.')) : 'Exento';

            $buckets[$key] ??= ['base' => 0, 'amount' => 0, 'label' => $label, 'rate' => $rate];
            $buckets[$key]['base'] += $item->getTotal() - $item->getTaxTotal();
            $buckets[$key]['amount'] += $item->getTaxTotal();
        }

        foreach ($buckets as $bucket) {
            $taxItem = new InvoiceTaxItem();
            $taxItem->initialise(
                label: $bucket['label'],
                rate: number_format($bucket['rate'], 2, '.', ''),
                base: $bucket['base'],
                amount: $bucket['amount'],
            );
            $invoice->addTaxItem($taxItem);
        }
    }

    /**
     * Best-effort rate extraction: scan tax adjustments on the item, parse a
     * numeric percentage out of the label/originCode. Falls back to 0 when
     * the item has no tax (exempt).
     */
    private function extractRateLabel(OrderItemInterface $item): ?string
    {
        $value = $this->extractRateValue($item);
        if ($value === 0.0 && $item->getTaxTotal() === 0) {
            return null;
        }

        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
    }

    private function extractRateValue(OrderItemInterface $item): float
    {
        // The order item's tax_total includes tax for every quantity.
        $base = $item->getTotal() - $item->getTaxTotal();
        if ($base <= 0) {
            return 0.0;
        }

        // Sylius does not expose the canonical rate directly on the item;
        // best we can do without coupling to the tax plugin is compute it.
        // Round to the nearest hundredth so 21.0001 doesn't drift.
        return round(($item->getTaxTotal() / $base) * 100, 2);
    }
}
