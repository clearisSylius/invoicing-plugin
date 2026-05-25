<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Command;

/**
 * Force regeneration of an invoice's PDF using the currently configured
 * template. Idempotent — overwrites the existing PDF on storage.
 *
 * Use sparingly: regenerating a long-emitted invoice with a NEW template
 * means the PDF no longer matches what the customer originally received.
 * The data in the invoice (numbers, totals, line items) is unchanged.
 */
final class RegeneratePdf
{
    public function __construct(
        public readonly int $invoiceId,
    ) {
    }
}
