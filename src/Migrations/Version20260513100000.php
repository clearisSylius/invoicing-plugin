<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Limpia los `tax_rate` heredados del plugin oficial.
 *
 * Las facturas importadas con `Version20260512100000` + el importer
 * inicial copiaron `tax_rate` literal desde el oficial, donde se guarda
 * con el símbolo `%` (ej. "21%"). El plugin nuevo asume que ese campo
 * es solo el número y el template añade el `%` al renderizar — sin
 * normalizar se ve "21%%" en pantalla y en el PDF.
 *
 * Strippeamos el `%` (y posibles espacios) de toda factura ya importada.
 * Solo afecta a filas con `legacy_id IS NOT NULL` para no tocar facturas
 * emitidas por el plugin nuevo, que ya guardan el formato correcto.
 */
final class Version20260513100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Strippea "%" de tax_rate en line items importados del plugin oficial';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            UPDATE clearis_invoicing_invoice_line_item li
            INNER JOIN clearis_invoicing_invoice i ON i.id = li.invoice_id
            SET li.tax_rate = TRIM(TRAILING '%' FROM TRIM(li.tax_rate))
            WHERE i.legacy_id IS NOT NULL
              AND li.tax_rate IS NOT NULL
              AND li.tax_rate LIKE '%\%'
        SQL);
    }

    public function down(Schema $schema): void
    {
        // No reversible: no podemos saber si la rate original llevaba `%`.
        // Si necesitas marcha atrás, vuelve a importar desde el oficial.
        $this->throwIrreversibleMigrationException();
    }
}
