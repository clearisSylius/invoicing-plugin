<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Event;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Raised when an invoice transitions to CANCELLED (i.e. after a total
 * rectification supersedes it).
 */
final class InvoiceCancelledEvent extends Event
{
    public function __construct(
        public readonly InvoiceInterface $invoice,
        public readonly InvoiceInterface $rectifyingInvoice,
    ) {
    }
}
