<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Event;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Raised after a rectifying invoice is persisted. Carries both the new
 * rectifying invoice and the original it rectifies.
 */
final class InvoiceRectifiedEvent extends Event
{
    public function __construct(
        public readonly InvoiceInterface $rectifyingInvoice,
        public readonly InvoiceInterface $originalInvoice,
        public readonly bool $isTotal,
    ) {
    }
}
