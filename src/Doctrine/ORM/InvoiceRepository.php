<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;

class InvoiceRepository extends EntityRepository implements InvoiceRepositoryInterface
{
    public function findByOrder(OrderInterface $order): array
    {
        /** @var list<InvoiceInterface> $invoices */
        $invoices = $this->createQueryBuilder('i')
            ->andWhere('i.order = :order')
            ->setParameter('order', $order)
            ->orderBy('i.issuedAt', 'ASC')
            ->addOrderBy('i.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;

        return $invoices;
    }

    public function findByLegacyId(string $legacyId): ?InvoiceInterface
    {
        /** @var InvoiceInterface|null $invoice */
        $invoice = $this->createQueryBuilder('i')
            ->andWhere('i.legacyId = :legacyId')
            ->setParameter('legacyId', $legacyId)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $invoice;
    }

    public function findMaxNumberForSeries(int $seriesId): ?string
    {
        /** @var string|null $number */
        $number = $this->createQueryBuilder('i')
            ->select('i.number')
            ->andWhere('IDENTITY(i.series) = :seriesId')
            ->setParameter('seriesId', $seriesId)
            ->orderBy('i.number', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult(\Doctrine\ORM\AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;

        return $number;
    }

    public function streamForBookExport(?ChannelInterface $channel, ?\DateTimeInterface $from, ?\DateTimeInterface $to): iterable
    {
        $qb = $this->createQueryBuilder('i')
            ->orderBy('i.issuedAt', 'ASC')
            ->addOrderBy('i.id', 'ASC')
        ;

        if ($channel !== null) {
            $qb->andWhere('i.channel = :channel')->setParameter('channel', $channel);
        }
        if ($from !== null) {
            $qb->andWhere('i.issuedAt >= :from')->setParameter('from', $from);
        }
        if ($to !== null) {
            $qb->andWhere('i.issuedAt <= :to')->setParameter('to', $to);
        }

        /** @var iterable<InvoiceInterface> $result */
        $result = $qb->getQuery()->toIterable();

        return $result;
    }
}
