<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Entity;

use ClearisSylius\InvoicingPlugin\Model\BillingDataInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceLineItemInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceStateEnum;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTaxItemInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use ClearisSylius\InvoicingPlugin\Model\ShopBillingDataInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

/**
 * The Invoice entity is the immutable contract between Sylius and the libro
 * registro. Once an instance is persisted, only `state` (workflow), `pdfPath`
 * (regeneration marker) and the lazy children collections may be touched.
 * Everything else is set once via the constructor and the package-private
 * setters used by the factory.
 *
 * Why all setters are missing for the "frozen" fields: the InvoiceFactory
 * constructs the entity once with the full snapshot; from then on Doctrine
 * loads it and the application reads it. There is no admin form that lets
 * you change `number`, `total`, etc. — that's correct, those would be tax
 * fraud surfaces.
 */
class Invoice implements InvoiceInterface
{
    protected ?int $id = null;

    /** @phpstan-var InvoiceTypeEnum::* */
    protected string $type;

    /** @phpstan-var InvoiceStateEnum::* */
    protected string $state = InvoiceStateEnum::ISSUED;

    protected string $number;

    protected ?InvoiceSeriesInterface $series = null;

    protected \DateTimeImmutable $issuedAt;

    protected OrderInterface $order;

    protected ChannelInterface $channel;

    protected string $currencyCode;

    protected string $localeCode;

    protected BillingDataInterface $billingData;

    protected ShopBillingDataInterface $shopBillingData;

    protected int $subtotal;

    protected int $taxesTotal;

    protected int $total;

    protected string $paymentState;

    protected ?InvoiceInterface $rectifiedInvoice = null;

    protected ?string $rectificationReason = null;

    protected ?string $pdfPath = null;

    /**
     * Identifier of the row in the official `sylius/invoicing-plugin`
     * `sylius_invoicing_plugin_invoice` table this invoice was imported
     * from. UUID/string because the official plugin (≥2.x) uses UUIDs as
     * primary keys; nullable because rows native to this plugin have
     * nothing to link to.
     */
    protected ?string $legacyId = null;

    /** @var Collection<int, InvoiceLineItemInterface> */
    protected Collection $lineItems;

    /** @var Collection<int, InvoiceTaxItemInterface> */
    protected Collection $taxItems;

    public function __construct()
    {
        $this->lineItems = new ArrayCollection();
        $this->taxItems = new ArrayCollection();
        $this->issuedAt = new \DateTimeImmutable();
        $this->type = InvoiceTypeEnum::STANDARD;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNumber(): string
    {
        return $this->number;
    }

    public function getSeries(): ?InvoiceSeriesInterface
    {
        return $this->series;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getIssuedAt(): \DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function getOrder(): OrderInterface
    {
        return $this->order;
    }

    public function getChannel(): ChannelInterface
    {
        return $this->channel;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function getLocaleCode(): string
    {
        return $this->localeCode;
    }

    public function getBillingData(): BillingDataInterface
    {
        return $this->billingData;
    }

    public function getShopBillingData(): ShopBillingDataInterface
    {
        return $this->shopBillingData;
    }

    public function getSubtotal(): int
    {
        return $this->subtotal;
    }

    public function getTaxesTotal(): int
    {
        return $this->taxesTotal;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function getPaymentState(): string
    {
        return $this->paymentState;
    }

    public function getState(): string
    {
        return $this->state;
    }

    /**
     * @phpstan-param InvoiceStateEnum::* $state
     */
    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getRectifiedInvoice(): ?InvoiceInterface
    {
        return $this->rectifiedInvoice;
    }

    public function getRectificationReason(): ?string
    {
        return $this->rectificationReason;
    }

    public function getPdfPath(): ?string
    {
        return $this->pdfPath;
    }

    public function setPdfPath(?string $pdfPath): void
    {
        $this->pdfPath = $pdfPath;
    }

    public function getLegacyId(): ?string
    {
        return $this->legacyId;
    }

    /** @return Collection<int, InvoiceLineItemInterface> */
    public function getLineItems(): Collection
    {
        return $this->lineItems;
    }

    public function addLineItem(InvoiceLineItemInterface $lineItem): void
    {
        if (!$this->lineItems->contains($lineItem)) {
            $this->lineItems->add($lineItem);
            $lineItem->setInvoice($this);
        }
    }

    /** @return Collection<int, InvoiceTaxItemInterface> */
    public function getTaxItems(): Collection
    {
        return $this->taxItems;
    }

    public function addTaxItem(InvoiceTaxItemInterface $taxItem): void
    {
        if (!$this->taxItems->contains($taxItem)) {
            $this->taxItems->add($taxItem);
            $taxItem->setInvoice($this);
        }
    }

    /**
     * Used only by the InvoiceFactory and RectifyingInvoiceFactory. Exposed
     * here rather than as constructor args to keep Doctrine hydration simple.
     *
     * @phpstan-param InvoiceTypeEnum::* $type
     */
    public function initialise(
        string $type,
        string $number,
        ?InvoiceSeriesInterface $series,
        OrderInterface $order,
        ChannelInterface $channel,
        string $currencyCode,
        string $localeCode,
        BillingDataInterface $billingData,
        ShopBillingDataInterface $shopBillingData,
        int $subtotal,
        int $taxesTotal,
        int $total,
        string $paymentState,
        ?InvoiceInterface $rectifiedInvoice = null,
        ?string $rectificationReason = null,
        ?string $legacyId = null,
        ?\DateTimeImmutable $issuedAt = null,
    ): void {
        $this->type = $type;
        $this->number = $number;
        $this->series = $series;
        $this->order = $order;
        $this->channel = $channel;
        $this->currencyCode = $currencyCode;
        $this->localeCode = $localeCode;
        $this->billingData = $billingData;
        $this->shopBillingData = $shopBillingData;
        $this->subtotal = $subtotal;
        $this->taxesTotal = $taxesTotal;
        $this->total = $total;
        $this->paymentState = $paymentState;
        $this->rectifiedInvoice = $rectifiedInvoice;
        $this->rectificationReason = $rectificationReason;
        $this->legacyId = $legacyId;
        if ($issuedAt !== null) {
            $this->issuedAt = $issuedAt;
        }
    }
}
