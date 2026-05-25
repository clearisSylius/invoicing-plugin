<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Doctrine\ORM;

use ClearisSylius\InvoicingPlugin\Model\InvoiceTemplateInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class InvoiceTemplateRepository extends EntityRepository
{
    public function findOneByCode(string $code): ?InvoiceTemplateInterface
    {
        /** @var InvoiceTemplateInterface|null $tpl */
        $tpl = $this->createQueryBuilder('t')
            ->andWhere('t.code = :code')
            ->setParameter('code', $code)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return $tpl;
    }
}
