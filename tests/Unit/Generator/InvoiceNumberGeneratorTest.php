<?php

declare(strict_types=1);

namespace Tests\ClearisSylius\InvoicingPlugin\Unit\Generator;

use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Generator\InvoiceNumberGenerator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Unit-level checks of the number generator's contract. The actual
 * concurrency guarantees are integration-tested separately because they
 * depend on the database driver's pessimistic locking semantics, which is
 * not faithfully simulated by mocks.
 */
final class InvoiceNumberGeneratorTest extends TestCase
{
    public function testIncrementsCounterAndFormatsNumber(): void
    {
        $series = new InvoiceSeries();
        $series->setCode('TEST');
        $series->setFormat('{year}/{number}');
        $series->setPadding(4);
        $series->setCurrentNumber(0);
        $series->setYearlyReset(true);
        $reflection = new \ReflectionClass($series);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($series, 1);

        $em = $this->createMock(EntityManagerInterface::class);

        $em->expects(self::once())
            ->method('wrapInTransaction')
            ->willReturnCallback(static fn (callable $fn) => $fn());

        $em->expects(self::once())
            ->method('find')
            ->with(InvoiceSeries::class, 1, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($series);

        $em->expects(self::once())->method('flush');

        $generator = new InvoiceNumberGenerator($em);
        $number = $generator->generate($series, new \DateTimeImmutable('2026-05-11'));

        self::assertSame('2026/0001', $number);
        self::assertSame(1, $series->getCurrentNumber());
        self::assertSame(2026, $series->getLastYearReset());
    }

    public function testYearlyResetTriggersOnYearChange(): void
    {
        $series = new InvoiceSeries();
        $series->setCode('TEST-RESET');
        $series->setFormat('{year}/{number}');
        $series->setPadding(3);
        $series->setCurrentNumber(57);
        $series->setLastYearReset(2025);
        $series->setYearlyReset(true);
        $reflection = new \ReflectionClass($series);
        $idProp = $reflection->getProperty('id');
        $idProp->setAccessible(true);
        $idProp->setValue($series, 2);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('wrapInTransaction')->willReturnCallback(static fn (callable $fn) => $fn());
        $em->method('find')->willReturn($series);

        $generator = new InvoiceNumberGenerator($em);
        $number = $generator->generate($series, new \DateTimeImmutable('2026-01-02'));

        self::assertSame('2026/001', $number);
        self::assertSame(1, $series->getCurrentNumber());
    }

    public function testRejectsUnpersistedSeries(): void
    {
        $series = new InvoiceSeries();
        $em = $this->createMock(EntityManagerInterface::class);

        $generator = new InvoiceNumberGenerator($em);

        $this->expectException(\InvalidArgumentException::class);
        $generator->generate($series, new \DateTimeImmutable());
    }
}
