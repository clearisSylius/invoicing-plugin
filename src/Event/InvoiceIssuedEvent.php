<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Event;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Public event raised after a new (standard) invoice is persisted. Subscribe
 * to this from third-party code to react to emissions without touching the
 * plugin internals (e.g. future VeriFactu/SII integrations).
 */
final class InvoiceIssuedEvent extends Event
{
    public function __construct(
        public readonly InvoiceInterface $invoice,
    ) {
    }
}
