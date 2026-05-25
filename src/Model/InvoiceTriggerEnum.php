<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Model;

/**
 * When the plugin should auto-emit an invoice for an order. Configured per
 * channel via ChannelInvoicingSettings.trigger.
 *
 * The "manual" mode disables every auto-trigger; admins emit the invoice
 * from the order show page button. Useful for B2B flows where billing
 * happens out of band.
 */
final class InvoiceTriggerEnum
{
    public const ON_PAYMENT_COMPLETED = 'on_payment_completed';

    public const ON_ORDER_SHIPPED = 'on_order_shipped';

    public const ON_ORDER_COMPLETED = 'on_order_completed';

    public const MANUAL = 'manual';

    /** @return list<string> */
    public static function all(): array
    {
        return [
            self::ON_PAYMENT_COMPLETED,
            self::ON_ORDER_SHIPPED,
            self::ON_ORDER_COMPLETED,
            self::MANUAL,
        ];
    }

    /** @return array<string, string> */
    public static function labels(): array
    {
        return [
            self::ON_PAYMENT_COMPLETED => 'clearis.invoice.trigger.on_payment_completed',
            self::ON_ORDER_SHIPPED => 'clearis.invoice.trigger.on_order_shipped',
            self::ON_ORDER_COMPLETED => 'clearis.invoice.trigger.on_order_completed',
            self::MANUAL => 'clearis.invoice.trigger.manual',
        ];
    }
}
