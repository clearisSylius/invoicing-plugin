<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Cambia `clearis_invoicing_invoice.legacy_id` de INT a VARCHAR(36).
 *
 * El plugin oficial `sylius/invoicing-plugin` 2.x usa UUID como PK de
 * `sylius_invoicing_plugin_invoice`. La migración inicial reservó la
 * columna como INT pensando en autoincrementales — error que ahora
 * corregimos. Idempotente: solo altera si el tipo actual no es ya
 * varchar.
 */
final class Version20260513090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Cambia clearis_invoicing_invoice.legacy_id a VARCHAR(36) para soportar UUIDs del plugin oficial';
    }

    public function up(Schema $schema): void
    {
        if (!$schema->hasTable('clearis_invoicing_invoice')) {
            return;
        }

        $table = $schema->getTable('clearis_invoicing_invoice');
        if (!$table->hasColumn('legacy_id')) {
            return;
        }

        $column = $table->getColumn('legacy_id');
        $currentType = $column->getType()->getName();

        if ($currentType === 'string') {
            // Ya migrado.
            return;
        }

        $this->addSql('ALTER TABLE clearis_invoicing_invoice MODIFY legacy_id VARCHAR(36) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$schema->hasTable('clearis_invoicing_invoice')) {
            return;
        }

        // CUIDADO: si hay filas con UUID, este down las truncará a NULL
        // porque no caben en un INT. Por eso primero limpiamos los datos
        // que vinieron del importer; los emitidos por el plugin nuevo no
        // llevan legacy_id, así que no se pierden.
        $this->addSql('UPDATE clearis_invoicing_invoice SET legacy_id = NULL WHERE legacy_id IS NOT NULL');
        $this->addSql('ALTER TABLE clearis_invoicing_invoice MODIFY legacy_id INT DEFAULT NULL');
    }
}
