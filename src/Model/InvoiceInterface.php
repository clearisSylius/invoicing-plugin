<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Doctrine\Common\Collections\Collection;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Invoice snapshot. Immutable after persistence — modifications must be
 * expressed as rectifying invoices, never by editing fields.
 *
 * All monetary values are integers in the currency's minor unit (céntimos
 * for EUR), the same convention Sylius core uses.
 */
interface InvoiceInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getNumber(): string;

    public function getSeries(): ?InvoiceSeriesInterface;

    /** @return InvoiceTypeEnum::* */
    public function getType(): string;

    public function getIssuedAt(): \DateTimeImmutable;

    public function getOrder(): OrderInterface;

    public function getChannel(): ChannelInterface;

    public function getCurrencyCode(): string;

    public function getLocaleCode(): string;

    public function getBillingData(): BillingDataInterface;

    public function getShopBillingData(): ShopBillingDataInterface;

    public function getSubtotal(): int;

    public function getTaxesTotal(): int;

    public function getTotal(): int;

    public function getPaymentState(): string;

    /** @return InvoiceStateEnum::* */
    public function getState(): string;

    public function setState(string $state): void;

    public function getRectifiedInvoice(): ?self;

    public function getRectificationReason(): ?string;

    public function getPdfPath(): ?string;

    public function setPdfPath(?string $pdfPath): void;

    public function getLegacyId(): ?string;

    /** @return Collection<int, InvoiceLineItemInterface> */
    public function getLineItems(): Collection;

    public function addLineItem(InvoiceLineItemInterface $lineItem): void;

    /** @return Collection<int, InvoiceTaxItemInterface> */
    public function getTaxItems(): Collection;

    public function addTaxItem(InvoiceTaxItemInterface $taxItem): void;
}
