<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class InvoiceSeriesRepository extends EntityRepository implements InvoiceSeriesRepositoryInterface
{
    public function findActiveByChannelAndType(ChannelInterface $channel, string $type): ?InvoiceSeriesInterface
    {
        /** @var InvoiceSeriesInterface|null $series */
        $series = $this->createQueryBuilder('s')
            ->andWhere('s.channel = :channel')
            ->andWhere('s.type = :type')
            ->andWhere('s.active = true')
            ->setParameter('channel', $channel)
            ->setParameter('type', $type)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $series;
    }

    public function findOneByCode(string $code): ?InvoiceSeriesInterface
    {
        /** @var InvoiceSeriesInterface|null $series */
        $series = $this->createQueryBuilder('s')
            ->andWhere('s.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $series;
    }
}
