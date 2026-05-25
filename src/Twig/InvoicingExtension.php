<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Twig;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface;
use ClearisSylius\InvoicingPlugin\Resolver\InvoiceTriggerResolverInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Exposes a handful of read-side helpers to admin templates so the order/
 * channel show pages can render plugin-specific snippets without leaking
 * repository lookups into Twig directly.
 *
 * Naming: prefix `sylius_get_clearis_*` to match the convention used by
 * Sylius admin templates for plugin helpers (the host application's hooks
 * search for that prefix).
 */
final class InvoicingExtension extends AbstractExtension
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly InvoiceTriggerResolverInterface $triggerResolver,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('sylius_get_clearis_invoices_for_order', $this->getInvoicesForOrder(...)),
            new TwigFunction('sylius_get_clearis_channel_trigger', $this->getChannelTrigger(...)),
            new TwigFunction('sylius_get_clearis_channel_settings', $this->getChannelSettings(...)),
        ];
    }

    /** @return list<\ClearisSylius\InvoicingPlugin\Model\InvoiceInterface> */
    public function getInvoicesForOrder(OrderInterface $order): array
    {
        return $this->invoiceRepository->findByOrder($order);
    }

    public function getChannelTrigger(ChannelInterface $channel): string
    {
        return $this->triggerResolver->resolveForChannel($channel);
    }

    public function getChannelSettings(ChannelInterface $channel): ?ChannelInvoicingSettingsInterface
    {
        return $this->settingsRepository->findByChannel($channel);
    }
}
