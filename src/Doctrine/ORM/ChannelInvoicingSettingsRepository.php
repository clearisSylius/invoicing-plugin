<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Sylius\Component\Channel\Model\ChannelInterface;

class ChannelInvoicingSettingsRepository extends EntityRepository
{
    public function findByChannel(ChannelInterface $channel): ?ChannelInvoicingSettingsInterface
    {
        /** @var ChannelInvoicingSettingsInterface|null $settings */
        $settings = $this->createQueryBuilder('s')
            ->andWhere('s.channel = :channel')
            ->setParameter('channel', $channel)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $settings;
    }
}
