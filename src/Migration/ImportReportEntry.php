<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migration;

/**
 * One row of the import report. Yielded from LegacyInvoiceImporter so the
 * caller (the console command) can render progress without buffering the
 * whole import.
 */
final class ImportReportEntry
{
    public const STATUS_IMPORTED = 'imported';

    public const STATUS_SKIPPED = 'skipped';

    public const STATUS_WARNING = 'warning';

    public function __construct(
        public readonly string $legacyId,
        public readonly string $number,
        public readonly string $status,
        public readonly ?string $message = null,
    ) {
    }
}
