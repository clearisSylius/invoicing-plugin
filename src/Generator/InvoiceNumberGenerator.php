<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Generator;

use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Model\InvoiceSeriesInterface;
// Doctrine ORM 3.x quitó `Doctrine\ORM\LockMode` (era alias de DBAL en 2.x).
// Sylius 2 ya viene con ORM 3, así que importamos directamente desde DBAL —
// que es donde vive la única definición canónica de la constante.
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Atomic invoice number generator.
 *
 * Wraps each call in a database transaction and SELECT ... FOR UPDATE on the
 * `clearis_invoicing_series` row so concurrent emissions queue serially.
 * Without this, a race produces duplicate numbers — which is undetectable at
 * the application level once both invoices are persisted, and a major fiscal
 * compliance issue in Spain (libro registro must be strictly sequential).
 *
 * The transaction is short by design (one row lock + counter bump + flush).
 * The actual Invoice insert happens AFTER this returns, in the caller's own
 * transaction.
 */
final class InvoiceNumberGenerator implements InvoiceNumberGeneratorInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function generate(InvoiceSeriesInterface $series, \DateTimeInterface $issuedAt): string
    {
        if ($series->getId() === null) {
            throw new \InvalidArgumentException('InvoiceSeries must be persisted before generating numbers.');
        }

        $year = (int) $issuedAt->format('Y');

        return $this->entityManager->wrapInTransaction(
            function () use ($series, $year): string {
                // Re-load the series under a pessimistic write lock; this is
                // what serialises concurrent generators. Doctrine emits
                // `SELECT ... FOR UPDATE` on the underlying connection.
                /** @var InvoiceSeries|null $locked */
                $locked = $this->entityManager->find(
                    InvoiceSeries::class,
                    $series->getId(),
                    LockMode::PESSIMISTIC_WRITE,
                );

                if ($locked === null) {
                    throw new \RuntimeException(sprintf(
                        'InvoiceSeries #%d disappeared during number generation.',
                        $series->getId(),
                    ));
                }

                // Yearly reset: if the series is configured to reset every
                // year and we crossed the year boundary, reset the counter
                // before incrementing.
                if ($locked->isYearlyReset()) {
                    $lastYear = $locked->getLastYearReset();
                    if ($lastYear === null || $lastYear !== $year) {
                        $locked->setCurrentNumber(0);
                        $locked->setLastYearReset($year);
                    }
                }

                $locked->setCurrentNumber($locked->getCurrentNumber() + 1);

                $this->entityManager->flush();

                return $locked->renderNumber($year);
            },
        );
    }
}
