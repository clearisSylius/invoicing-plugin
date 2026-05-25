<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Añade los campos de personalización a `clearis_invoicing_template`.
 *
 * El plugin originalmente solo guardaba `code`, `name`, `channel`, `type`,
 * `twig_template`, `logo_path` y `footer_text`. La plantilla PDF estaba
 * cableada en el .twig — el admin no podía cambiar nada visual sin tocar
 * código. Esta migración añade:
 *
 *   - Branding: `accent_color`, `layout_density`, `show_logo`
 *   - Cabecera: `header_text`, `header_contact_info`
 *   - Cliente: `show_customer_email`, `show_customer_shipping_address`
 *   - Líneas: `show_line_sku`, `show_line_extended_description`, `show_line_discounts`
 *   - Totales: `show_tax_breakdown`
 *   - Pedido: `show_order_number`, `show_payment_method`, `show_shipping_method`
 *   - Textos: `payment_terms_text`, `legal_notes_text`
 *
 * Los booleanos llevan defaults pensados para no romper el render existente
 * (la plantilla por defecto sigue mostrando todo lo que mostraba antes).
 *
 * Mantiene la migración idempotente verificando si las columnas ya existen
 * antes de añadirlas — útil al desplegar sobre BDs donde se hizo prueba
 * manual previa.
 */
final class Version20260518120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Añade campos de personalización a clearis_invoicing_template';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('clearis_invoicing_template');

        // Branding ------------------------------------------------------
        if (!$table->hasColumn('accent_color')) {
            $this->addSql("ALTER TABLE clearis_invoicing_template ADD accent_color VARCHAR(16) NOT NULL DEFAULT '#222222'");
        }
        if (!$table->hasColumn('layout_density')) {
            $this->addSql("ALTER TABLE clearis_invoicing_template ADD layout_density VARCHAR(16) NOT NULL DEFAULT 'regular'");
        }
        if (!$table->hasColumn('show_logo')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_logo TINYINT(1) NOT NULL DEFAULT 1');
        }

        // Cabecera ------------------------------------------------------
        if (!$table->hasColumn('header_text')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD header_text LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('header_contact_info')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD header_contact_info LONGTEXT DEFAULT NULL');
        }

        // Cliente -------------------------------------------------------
        if (!$table->hasColumn('show_customer_email')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_customer_email TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$table->hasColumn('show_customer_shipping_address')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_customer_shipping_address TINYINT(1) NOT NULL DEFAULT 0');
        }

        // Líneas --------------------------------------------------------
        if (!$table->hasColumn('show_line_sku')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_line_sku TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$table->hasColumn('show_line_extended_description')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_line_extended_description TINYINT(1) NOT NULL DEFAULT 1');
        }
        if (!$table->hasColumn('show_line_discounts')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_line_discounts TINYINT(1) NOT NULL DEFAULT 0');
        }

        // Totales -------------------------------------------------------
        if (!$table->hasColumn('show_tax_breakdown')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_tax_breakdown TINYINT(1) NOT NULL DEFAULT 1');
        }

        // Referencias del pedido ---------------------------------------
        if (!$table->hasColumn('show_order_number')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_order_number TINYINT(1) NOT NULL DEFAULT 1');
        }
        if (!$table->hasColumn('show_payment_method')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_payment_method TINYINT(1) NOT NULL DEFAULT 0');
        }
        if (!$table->hasColumn('show_shipping_method')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD show_shipping_method TINYINT(1) NOT NULL DEFAULT 0');
        }

        // Bloques de texto libres --------------------------------------
        if (!$table->hasColumn('payment_terms_text')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD payment_terms_text LONGTEXT DEFAULT NULL');
        }
        if (!$table->hasColumn('legal_notes_text')) {
            $this->addSql('ALTER TABLE clearis_invoicing_template ADD legal_notes_text LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE clearis_invoicing_template
            DROP accent_color,
            DROP layout_density,
            DROP show_logo,
            DROP header_text,
            DROP header_contact_info,
            DROP show_customer_email,
            DROP show_customer_shipping_address,
            DROP show_line_sku,
            DROP show_line_extended_description,
            DROP show_line_discounts,
            DROP show_tax_breakdown,
            DROP show_order_number,
            DROP show_payment_method,
            DROP show_shipping_method,
            DROP payment_terms_text,
            DROP legal_notes_text');
    }
}
