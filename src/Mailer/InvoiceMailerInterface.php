<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Mailer;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;

interface InvoiceMailerInterface
{
    /**
     * Email the customer their invoice with the PDF attached.
     *
     * `$senderAddress` is resolved by the caller (typically the listener
     * pulls it from `ChannelInvoicingSettings.senderEmail` with fallback
     * to the global plugin parameter). If null/empty, the mailer no-ops
     * silently — the invoice issuance is independent of email delivery.
     *
     * Idempotency is NOT enforced here — callers (event listeners) decide
     * when to fire. The mailer itself just renders + sends.
     */
    public function sendInvoiceIssued(
        InvoiceInterface $invoice,
        ?string $senderAddress,
        ?string $senderName = null,
    ): void;

    public function sendRectifyingInvoiceIssued(
        InvoiceInterface $rectifying,
        InvoiceInterface $original,
        ?string $senderAddress,
        ?string $senderName = null,
    ): void;
}
