<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

/**
 * Distinguishes a normal sales invoice from a rectifying invoice (factura
 * rectificativa). Rectifying invoices carry their own sequential number from
 * a dedicated InvoiceSeries.
 */
final class InvoiceTypeEnum
{
    public const STANDARD = 'standard';

    public const RECTIFYING = 'rectifying';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::STANDARD, self::RECTIFYING];
    }
}
