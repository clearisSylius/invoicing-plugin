<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<InvoiceSeriesInterface>
 */
interface InvoiceSeriesRepositoryInterface extends RepositoryInterface
{
    public function findActiveByChannelAndType(ChannelInterface $channel, string $type): ?InvoiceSeriesInterface;

    public function findOneByCode(string $code): ?InvoiceSeriesInterface;
}
