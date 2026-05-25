<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Resource\Doctrine\Persistence\RepositoryInterface;

/**
 * @extends RepositoryInterface<InvoiceInterface>
 */
interface InvoiceRepositoryInterface extends RepositoryInterface
{
    /** @return list<InvoiceInterface> */
    public function findByOrder(OrderInterface $order): array;

    public function findByLegacyId(string $legacyId): ?InvoiceInterface;

    /**
     * Returns the highest numeric counter (parsed) across invoices of a given
     * series, used by the import command to seed `currentNumber` so future
     * emissions continue the existing sequence.
     */
    public function findMaxNumberForSeries(int $seriesId): ?string;

    /**
     * @return iterable<InvoiceInterface>
     */
    public function streamForBookExport(
        ?ChannelInterface $channel,
        ?\DateTimeInterface $from,
        ?\DateTimeInterface $to,
    ): iterable;
}
