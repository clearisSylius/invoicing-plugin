<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Resolver;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTriggerEnum;
use Sylius\Component\Channel\Model\ChannelInterface;

/**
 * Resolves the configured invoice trigger for a channel, falling back to
 * `manual` if no ChannelInvoicingSettings has been created for that channel.
 * The fallback is intentionally conservative: better to fail to auto-emit
 * than to emit with a wrong (default) configuration.
 */
final class InvoiceTriggerResolver implements InvoiceTriggerResolverInterface
{
    public function __construct(
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
    ) {
    }

    public function resolveForChannel(ChannelInterface $channel): string
    {
        $settings = $this->settingsRepository->findByChannel($channel);
        if ($settings === null) {
            return InvoiceTriggerEnum::MANUAL;
        }

        return $settings->getTrigger();
    }
}
