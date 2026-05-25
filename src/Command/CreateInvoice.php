<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Command;

/**
 * Messenger command: emit a new invoice for the order with this number.
 *
 * We address the order by its `number` (Sylius's natural identifier) rather
 * than by primary key so the command is safe to enqueue and replay across
 * environments where ids may not match.
 */
final class CreateInvoice
{
    public function __construct(
        public readonly string $orderNumber,
    ) {
    }
}
