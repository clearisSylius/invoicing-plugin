<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\EventListener;

use ClearisSylius\InvoicingPlugin\Command\CreateInvoice;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use ClearisSylius\InvoicingPlugin\Resolver\InvoiceTriggerResolverInterface;
use ClearisSylius\InvoicingPlugin\Resolver\LegacyModeChecker;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Fires CreateInvoice when an order completes IF the channel is configured
 * with the on_order_completed trigger.
 *
 * When `clearis_sylius_invoicing.legacy_mode` is true, the listener is wired
 * as a no-op in the container (no service registered) — that's how we keep
 * coexistence with sylius/invoicing-plugin during migration.
 */
final class OrderCompletedListener
{
    public function __construct(
        private readonly InvoiceTriggerResolverInterface $triggerResolver,
        private readonly MessageBusInterface $commandBus,
        private readonly LegacyModeChecker $legacyMode,
    ) {
    }

    public function __invoke(GenericEvent $event): void
    {
        if ($this->legacyMode->isLegacyMode()) {
            return;
        }

        $order = $event->getSubject();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        if ($this->triggerResolver->resolveForChannel($channel) !== InvoiceTriggerEnum::ON_ORDER_COMPLETED) {
            return;
        }

        $this->commandBus->dispatch(new CreateInvoice((string) $order->getNumber()));
    }
}
