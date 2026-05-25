<?php

declare(strict_types=1);

use ClearisSylius\InvoicingPlugin\Entity\Invoice;
use ClearisSylius\InvoicingPlugin\Model\InvoiceStateEnum;

/**
 * Symfony Workflow definition for Invoice state machine.
 *
 * - ISSUED is the entry state for any Invoice the moment it is persisted.
 * - CANCELLED is reached only when a rectifying invoice that totally rectifies
 *   the original is emitted; the InvoiceFactory triggers the transition.
 *
 * We use the "state_machine" type so transitions are exclusive (an invoice is
 * either issued OR cancelled, never both).
 */

return [
    'clearis_invoice' => [
        'type' => 'state_machine',
        'supports' => [Invoice::class],
        'marking_store' => [
            'type' => 'method',
            'property' => 'state',
        ],
        'initial_marking' => InvoiceStateEnum::ISSUED,
        'places' => [
            InvoiceStateEnum::ISSUED,
            InvoiceStateEnum::CANCELLED,
        ],
        'transitions' => [
            'cancel' => [
                'from' => InvoiceStateEnum::ISSUED,
                'to' => InvoiceStateEnum::CANCELLED,
            ],
        ],
    ],
];
