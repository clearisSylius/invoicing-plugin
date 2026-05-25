<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;

interface RectifyingInvoiceFactoryInterface
{
    /**
     * Build a rectifying invoice that supersedes (totally or partially) an
     * existing one.
     *
     * @param array{base?: int, taxes?: int, total?: int} $partialAmounts
     *        When the rectification is partial these override the totals on
     *        the new invoice; for a total rectification leave the array empty
     *        and the factory negates the original amounts.
     */
    public function create(
        InvoiceInterface $original,
        string $reason,
        bool $isTotal,
        array $partialAmounts = [],
    ): InvoiceInterface;
}
