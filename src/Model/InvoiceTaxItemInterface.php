<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

use Sylius\Component\Resource\Model\ResourceInterface;

/**
 * Aggregated tax breakdown row at the invoice level. One TaxItem per distinct
 * tax rate present on the invoice (e.g. an invoice with lines at 21% and 10%
 * produces two TaxItem rows).
 *
 * This shape is what the libro registro de facturas emitidas expects: bases
 * and quotas grouped by rate.
 */
interface InvoiceTaxItemInterface extends ResourceInterface
{
    public function getId(): ?int;

    public function getInvoice(): ?InvoiceInterface;

    public function setInvoice(?InvoiceInterface $invoice): void;

    /** Human label, e.g. "IVA 21%" — used on the PDF and libro export. */
    public function getLabel(): string;

    /** Decimal rate as a string with two decimals, e.g. "21.00". */
    public function getRate(): string;

    /** Tax base in cents. */
    public function getBase(): int;

    /** Tax amount in cents. */
    public function getAmount(): int;
}
