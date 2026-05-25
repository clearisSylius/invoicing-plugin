<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Factory;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Sylius\Component\Core\Model\OrderInterface;

interface InvoiceFactoryInterface
{
    /**
     * Build an Invoice (type=STANDARD) from a completed order. The returned
     * entity is fully populated, has its number reserved on the active series
     * and is ready to be persisted by the caller.
     */
    public function createFromOrder(OrderInterface $order): InvoiceInterface;
}
