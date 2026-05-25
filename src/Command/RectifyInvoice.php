<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Command;

/**
 * Messenger command: emit a rectifying invoice for the given original.
 *
 * @phpstan-import-type RectificationReason from \ClearisSylius\InvoicingPlugin\Model\RectificationReasonEnum
 */
final class RectifyInvoice
{
    /**
     * @param RectificationReason $reason RectificationReasonEnum::*
     * @param bool                $isTotal     If true, the rectifying invoice
     *                                         fully supersedes the original
     *                                         (counter-entry). If false, the
     *                                         partial amounts apply.
     * @param int                 $baseDelta   Base amount in cents
     *                                         (negative = refund).
     * @param int                 $taxesDelta  Tax amount in cents.
     * @param int                 $totalDelta  Gross total (computed when 0).
     */
    public function __construct(
        public readonly int $originalInvoiceId,
        public readonly string $reason,
        public readonly bool $isTotal = true,
        public readonly int $baseDelta = 0,
        public readonly int $taxesDelta = 0,
        public readonly int $totalDelta = 0,
    ) {
    }
}
