<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migration;

use Doctrine\DBAL\Connection;

/**
 * Decide which legacy-data path we have available:
 *
 *  - PATH_ORM: the official plugin's classes are autoloaded AND its tables
 *              exist. We can use the official ORM repository directly.
 *  - PATH_SQL: the official tables exist but the classes are gone (host
 *              already uninstalled the package but kept the data). Read
 *              with raw SQL via DBAL.
 *  - PATH_NONE: nothing to import.
 *
 * The decision affects only the import command; the rest of the plugin runs
 * the same regardless.
 */
final class LegacyDetector
{
    public const PATH_ORM = 'orm';

    public const PATH_SQL = 'sql';

    public const PATH_NONE = 'none';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /** @return self::PATH_* */
    public function detect(): string
    {
        $hasTable = $this->tableExists('sylius_invoicing_plugin_invoice');

        if ($hasTable && class_exists(\Sylius\InvoicingPlugin\Entity\Invoice::class)) {
            return self::PATH_ORM;
        }

        if ($hasTable) {
            return self::PATH_SQL;
        }

        return self::PATH_NONE;
    }

    private function tableExists(string $table): bool
    {
        try {
            return $this->connection->createSchemaManager()->tablesExist([$table]);
        } catch (\Throwable) {
            return false;
        }
    }
}
