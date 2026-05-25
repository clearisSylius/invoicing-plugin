<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Pdf;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;

interface InvoicePdfGeneratorInterface
{
    /**
     * Render and persist the PDF for an invoice; return the storage path.
     */
    public function generate(InvoiceInterface $invoice): string;
}
