<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

interface InvoiceLineItemInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getInvoice(): ?InvoiceInterface;

    public function setInvoice(?InvoiceInterface $invoice): void;

    public function getName(): string;

    public function getVariantName(): ?string;

    public function getVariantCode(): ?string;

    public function getQuantity(): int;

    /** Net unit price in cents (before discounts). */
    public function getUnitPrice(): int;

    /** Net unit price in cents after order-level + line-level discounts. */
    public function getDiscountedUnitNetPrice(): int;

    /** Line subtotal in cents (net of taxes, after discounts). */
    public function getSubtotal(): int;

    /** Free-form tax rate label as found on the order ("21", "21.00", "VAT 21%", null when exempt). */
    public function getTaxRate(): ?string;

    /** Tax amount in cents charged on this line. */
    public function getTaxTotal(): int;

    /** Gross total in cents (subtotal + taxTotal). */
    public function getTotal(): int;
}
