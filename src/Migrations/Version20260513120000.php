<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Recalcula `base` de los `clearis_invoicing_invoice_tax_item` de facturas
 * importadas desde el plugin oficial.
 *
 * El importer inicial reconstruía `base` con la fórmula `amount * 100 / rate`,
 * que arrastra los redondeos a céntimos del oficial (ej. tax amount=183¢
 * para una base de 869¢ daba base=871¢, dos céntimos arriba). El fix del
 * importer (Version siguiente) suma `subtotal` de los `line_item` que
 * comparten la misma rate, que es exacto al céntimo — pero solo afecta
 * a importaciones futuras.
 *
 * Esta migración hace ese mismo recálculo SQL-only para las facturas
 * que ya están en BD: por cada tax_item con invoice.legacy_id IS NOT NULL,
 * suma el `subtotal` de los line_items de la misma factura cuya `tax_rate`
 * (sin `%`) case con la rate del tax_item.
 *
 * Idempotente: relanzar no cambia nada porque ya estaría ajustada.
 *
 * Requiere que la migración `Version20260513100000` (strip de `%` en
 * `tax_rate`) haya corrido antes — el join asume que `tax_rate` es solo
 * el número.
 */
final class Version20260513120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Recalcula base de tax_items importados sumando subtotal de line_items por rate';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE clearis_invoicing_invoice_tax_item t
            INNER JOIN clearis_invoicing_invoice i ON i.id = t.invoice_id
            INNER JOIN (
                SELECT
                    li.invoice_id,
                    CAST(TRIM(TRAILING '%' FROM TRIM(li.tax_rate)) AS DECIMAL(6, 2)) AS rate_num,
                    SUM(li.subtotal) AS base_sum
                FROM clearis_invoicing_invoice_line_item li
                WHERE li.tax_rate IS NOT NULL
                  AND TRIM(li.tax_rate) <> ''
                GROUP BY li.invoice_id, rate_num
            ) agg ON agg.invoice_id = t.invoice_id
                AND agg.rate_num = CAST(t.rate AS DECIMAL(6, 2))
            SET t.base_amount = agg.base_sum
            WHERE i.legacy_id IS NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // No reversible: la fórmula vieja perdía información.
        $this->throwIrreversibleMigrationException();
    }
}
