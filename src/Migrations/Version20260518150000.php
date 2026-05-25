<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Mueve el sender de email del env var global a configuración per-canal.
 *
 * Antes:
 *   - El plugin requería `CLEARIS_INVOICING_SENDER` como env var, y todas
 *     las facturas de todas las tiendas se enviaban desde la misma dirección.
 *     Si el env no estaba seteado, el container petaba en compile time.
 *
 * Ahora:
 *   - `ChannelInvoicingSettings` lleva `sender_email` y `sender_name`
 *     opcionales. Si están vacíos, el listener cae al default global
 *     (parameter `clearis_sylius_invoicing.email.sender_address`, que ya
 *     no requiere env var y por defecto es `null` → silently skip).
 *   - Si el env var existía, el host lo puede mantener vía
 *     `clearis_sylius_invoicing.email.sender_address` en su YAML.
 *
 * Migración: solo añade las dos columnas. Idempotente.
 */
final class Version20260518150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade sender_email y sender_name por canal a clearis_invoicing_channel_settings';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('clearis_invoicing_channel_settings');

        if (!$table->hasColumn('sender_email')) {
            $this->addSql('ALTER TABLE clearis_invoicing_channel_settings ADD sender_email VARCHAR(255) DEFAULT NULL');
        }
        if (!$table->hasColumn('sender_name')) {
            $this->addSql('ALTER TABLE clearis_invoicing_channel_settings ADD sender_name VARCHAR(255) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clearis_invoicing_channel_settings DROP sender_email, DROP sender_name');
    }
}
