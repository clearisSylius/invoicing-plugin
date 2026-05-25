<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\EventListener;

use ClearisSylius\InvoicingPlugin\Command\CreateInvoice;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use ClearisSylius\InvoicingPlugin\Resolver\InvoiceTriggerResolverInterface;
use ClearisSylius\InvoicingPlugin\Resolver\LegacyModeChecker;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\ShipmentInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class ShipmentShippedListener
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

        $shipment = $event->getSubject();
        if (!$shipment instanceof ShipmentInterface) {
            return;
        }

        $order = $shipment->getOrder();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        if ($this->triggerResolver->resolveForChannel($channel) !== InvoiceTriggerEnum::ON_ORDER_SHIPPED) {
            return;
        }

        $this->commandBus->dispatch(new CreateInvoice((string) $order->getNumber()));
    }
}
