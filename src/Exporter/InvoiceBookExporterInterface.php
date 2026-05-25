<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Exporter;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

interface InvoiceBookExporterInterface
{
    /**
     * Build the libro registro de facturas emitidas spreadsheet for the given
     * invoice stream. The stream is consumed once; pass a fresh iterable each
     * time the export is rebuilt.
     *
     * @param iterable<InvoiceInterface> $invoices
     */
    public function export(iterable $invoices): Spreadsheet;
}
