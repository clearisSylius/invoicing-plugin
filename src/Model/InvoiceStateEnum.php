<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

/**
 * Workflow places for an Invoice (see config/workflow/invoice.php).
 *
 * - ISSUED   : invoice is live, listed in the libro registro.
 * - CANCELLED: the invoice has been fully rectified by a later rectifying
 *              invoice and should still appear in the book but is marked as
 *              superseded.
 */
final class InvoiceStateEnum
{
    public const ISSUED = 'issued';

    public const CANCELLED = 'cancelled';

    /** @return list<string> */
    public static function all(): array
    {
        return [self::ISSUED, self::CANCELLED];
    }
}
