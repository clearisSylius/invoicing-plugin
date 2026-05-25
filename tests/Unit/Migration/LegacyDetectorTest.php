<?php

declare(strict_types=1);

namespace Tests\ClearisSylius\InvoicingPlugin\Unit\Migration;

use ClearisSylius\InvoicingPlugin\Migration\LegacyDetector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use PHPUnit\Framework\TestCase;

final class LegacyDetectorTest extends TestCase
{
    public function testReturnsNoneWhenTableMissing(): void
    {
        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(false);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $detector = new LegacyDetector($connection);

        self::assertSame(LegacyDetector::PATH_NONE, $detector->detect());
    }

    public function testReturnsSqlWhenTablePresentButClassesMissing(): void
    {
        if (class_exists(\Sylius\InvoicingPlugin\Entity\Invoice::class)) {
            self::markTestSkipped('Cannot exercise PATH_SQL when the official plugin classes are autoloaded.');
        }

        $schemaManager = $this->createMock(AbstractSchemaManager::class);
        $schemaManager->method('tablesExist')->willReturn(true);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);

        $detector = new LegacyDetector($connection);

        self::assertSame(LegacyDetector::PATH_SQL, $detector->detect());
    }
}
