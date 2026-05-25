<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Generator;

use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;

interface InvoiceNumberGeneratorInterface
{
    /**
     * Reserve and return the next invoice number for the given series.
     *
     * Implementations MUST use a pessimistic write lock on the series row so
     * concurrent emissions never produce the same number. The counter on the
     * series row is mutated and flushed before returning.
     */
    public function generate(InvoiceSeriesInterface $series, \DateTimeInterface $issuedAt): string;
}
