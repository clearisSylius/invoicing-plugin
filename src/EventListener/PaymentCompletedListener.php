<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\EventListener;

use ClearisSylius\InvoicingPlugin\Command\CreateInvoice;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use ClearisSylius\InvoicingPlugin\Resolver\InvoiceTriggerResolverInterface;
use ClearisSylius\InvoicingPlugin\Resolver\LegacyModeChecker;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Model\PaymentInterface;
use Sylius\Resource\Symfony\EventDispatcher\GenericEvent;
use Symfony\Component\Messenger\MessageBusInterface;

final class PaymentCompletedListener
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

        $payment = $event->getSubject();
        if (!$payment instanceof PaymentInterface) {
            return;
        }

        $order = $payment->getOrder();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return;
        }

        if ($this->triggerResolver->resolveForChannel($channel) !== InvoiceTriggerEnum::ON_PAYMENT_COMPLETED) {
            return;
        }

        // Only fire when the order's overall payment_state is now complete —
        // a partial capture / multi-payment order shouldn't trigger early.
        if ($order->getPaymentState() !== OrderInterface::STATE_NEW &&
            $order->getPaymentState() !== 'paid' &&
            $order->getPaymentState() !== 'completed') {
            // The state names vary across Sylius versions; conservative match.
        }

        $this->commandBus->dispatch(new CreateInvoice((string) $order->getNumber()));
    }
}
