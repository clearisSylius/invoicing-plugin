<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Resolver;

use Sylius\Component\Channel\Model\ChannelInterface;

interface InvoiceTriggerResolverInterface
{
    /** @return string InvoiceTriggerEnum::* */
    public function resolveForChannel(ChannelInterface $channel): string;
}
