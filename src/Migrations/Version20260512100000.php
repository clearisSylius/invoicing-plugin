<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Creates the full schema for `clearissylius/invoicing-plugin` and adds the
 * `tax_id` column to `sylius_address`. Idempotent: every CREATE/ALTER guards
 * against pre-existing state so the migration can run safely on a host that
 * already has partial data from a prior install.
 */
final class Version20260512100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Clearis invoicing plugin: create invoice/series/template/billing-data tables and extend sylius_address with tax_id';
    }

    public function up(Schema $schema): void
    {
        // sylius_address.tax_id
        if ($schema->hasTable('sylius_address')) {
            $table = $schema->getTable('sylius_address');
            if (!$table->hasColumn('tax_id')) {
                $this->addSql('ALTER TABLE sylius_address ADD tax_id VARCHAR(20) DEFAULT NULL');
            }
        }

        // clearis_invoicing_billing_data
        if (!$schema->hasTable('clearis_invoicing_billing_data')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_billing_data (
                    id INT AUTO_INCREMENT NOT NULL,
                    first_name VARCHAR(255) DEFAULT NULL,
                    last_name VARCHAR(255) DEFAULT NULL,
                    company VARCHAR(255) DEFAULT NULL,
                    tax_id VARCHAR(20) DEFAULT NULL,
                    street VARCHAR(255) NOT NULL,
                    city VARCHAR(255) NOT NULL,
                    postcode VARCHAR(32) NOT NULL,
                    province_code VARCHAR(32) DEFAULT NULL,
                    province_name VARCHAR(255) DEFAULT NULL,
                    country_code VARCHAR(2) NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_shop_billing_data
        if (!$schema->hasTable('clearis_invoicing_shop_billing_data')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_shop_billing_data (
                    id INT AUTO_INCREMENT NOT NULL,
                    company_name VARCHAR(255) NOT NULL,
                    tax_id VARCHAR(20) NOT NULL,
                    street VARCHAR(255) NOT NULL,
                    city VARCHAR(255) NOT NULL,
                    postcode VARCHAR(32) NOT NULL,
                    country_code VARCHAR(2) NOT NULL,
                    PRIMARY KEY(id)
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_series
        if (!$schema->hasTable('clearis_invoicing_series')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_series (
                    id INT AUTO_INCREMENT NOT NULL,
                    channel_id INT DEFAULT NULL,
                    code VARCHAR(64) NOT NULL,
                    name VARCHAR(255) DEFAULT NULL,
                    type VARCHAR(32) NOT NULL,
                    format VARCHAR(64) NOT NULL,
                    padding SMALLINT NOT NULL,
                    current_number INT NOT NULL,
                    last_year_reset INT DEFAULT NULL,
                    yearly_reset TINYINT(1) NOT NULL,
                    active TINYINT(1) NOT NULL,
                    UNIQUE INDEX uq_series_code (code),
                    INDEX IDX_series_channel (channel_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_series_channel FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_template
        if (!$schema->hasTable('clearis_invoicing_template')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_template (
                    id INT AUTO_INCREMENT NOT NULL,
                    channel_id INT DEFAULT NULL,
                    code VARCHAR(64) NOT NULL,
                    name VARCHAR(255) DEFAULT NULL,
                    twig_template VARCHAR(255) NOT NULL,
                    logo_path VARCHAR(255) DEFAULT NULL,
                    footer_text TEXT DEFAULT NULL,
                    type VARCHAR(32) NOT NULL,
                    UNIQUE INDEX uq_template_code (code),
                    INDEX IDX_template_channel (channel_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_template_channel FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_invoice
        if (!$schema->hasTable('clearis_invoicing_invoice')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_invoice (
                    id INT AUTO_INCREMENT NOT NULL,
                    series_id INT DEFAULT NULL,
                    order_id INT NOT NULL,
                    channel_id INT NOT NULL,
                    billing_data_id INT NOT NULL,
                    shop_billing_data_id INT NOT NULL,
                    rectified_invoice_id INT DEFAULT NULL,
                    type VARCHAR(32) NOT NULL,
                    state VARCHAR(32) NOT NULL,
                    number VARCHAR(32) NOT NULL,
                    issued_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
                    currency_code VARCHAR(3) NOT NULL,
                    locale_code VARCHAR(12) NOT NULL,
                    subtotal INT NOT NULL,
                    taxes_total INT NOT NULL,
                    total INT NOT NULL,
                    payment_state VARCHAR(32) NOT NULL,
                    rectification_reason VARCHAR(8) DEFAULT NULL,
                    pdf_path VARCHAR(255) DEFAULT NULL,
                    legacy_id INT DEFAULT NULL,
                    INDEX idx_invoice_number (number),
                    INDEX idx_invoice_issued_at (issued_at),
                    INDEX idx_invoice_legacy_id (legacy_id),
                    UNIQUE INDEX uq_invoice_legacy_id (legacy_id),
                    INDEX IDX_invoice_series (series_id),
                    INDEX IDX_invoice_order (order_id),
                    INDEX IDX_invoice_channel (channel_id),
                    UNIQUE INDEX UQ_invoice_billing_data (billing_data_id),
                    UNIQUE INDEX UQ_invoice_shop_billing_data (shop_billing_data_id),
                    INDEX IDX_invoice_rectified (rectified_invoice_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_invoice_series FOREIGN KEY (series_id) REFERENCES clearis_invoicing_series (id) ON DELETE SET NULL,
                    CONSTRAINT FK_invoice_order FOREIGN KEY (order_id) REFERENCES sylius_order (id) ON DELETE RESTRICT,
                    CONSTRAINT FK_invoice_channel FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE RESTRICT,
                    CONSTRAINT FK_invoice_billing FOREIGN KEY (billing_data_id) REFERENCES clearis_invoicing_billing_data (id),
                    CONSTRAINT FK_invoice_shop_billing FOREIGN KEY (shop_billing_data_id) REFERENCES clearis_invoicing_shop_billing_data (id),
                    CONSTRAINT FK_invoice_rectified FOREIGN KEY (rectified_invoice_id) REFERENCES clearis_invoicing_invoice (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_invoice_line_item
        if (!$schema->hasTable('clearis_invoicing_invoice_line_item')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_invoice_line_item (
                    id INT AUTO_INCREMENT NOT NULL,
                    invoice_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    variant_name VARCHAR(255) DEFAULT NULL,
                    variant_code VARCHAR(255) DEFAULT NULL,
                    quantity INT NOT NULL,
                    unit_price INT NOT NULL,
                    discounted_unit_net_price INT NOT NULL,
                    subtotal INT NOT NULL,
                    tax_rate VARCHAR(32) DEFAULT NULL,
                    tax_total INT NOT NULL,
                    total INT NOT NULL,
                    INDEX IDX_line_invoice (invoice_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_line_invoice FOREIGN KEY (invoice_id) REFERENCES clearis_invoicing_invoice (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_invoice_tax_item
        if (!$schema->hasTable('clearis_invoicing_invoice_tax_item')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_invoice_tax_item (
                    id INT AUTO_INCREMENT NOT NULL,
                    invoice_id INT NOT NULL,
                    label VARCHAR(64) NOT NULL,
                    rate NUMERIC(6, 2) NOT NULL,
                    base_amount INT NOT NULL,
                    amount INT NOT NULL,
                    INDEX IDX_tax_invoice (invoice_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_tax_invoice FOREIGN KEY (invoice_id) REFERENCES clearis_invoicing_invoice (id) ON DELETE CASCADE
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }

        // clearis_invoicing_channel_settings
        if (!$schema->hasTable('clearis_invoicing_channel_settings')) {
            $this->addSql(<<<'SQL'
                CREATE TABLE clearis_invoicing_channel_settings (
                    id INT AUTO_INCREMENT NOT NULL,
                    channel_id INT NOT NULL,
                    standard_template_id INT DEFAULT NULL,
                    rectifying_template_id INT DEFAULT NULL,
                    standard_series_id INT DEFAULT NULL,
                    rectifying_series_id INT DEFAULT NULL,
                    shop_billing_data_id INT DEFAULT NULL,
                    trigger_mode VARCHAR(64) NOT NULL,
                    send_email_on_issue TINYINT(1) NOT NULL,
                    UNIQUE INDEX uq_channel_settings_channel (channel_id),
                    INDEX IDX_settings_std_tpl (standard_template_id),
                    INDEX IDX_settings_rect_tpl (rectifying_template_id),
                    INDEX IDX_settings_std_series (standard_series_id),
                    INDEX IDX_settings_rect_series (rectifying_series_id),
                    INDEX IDX_settings_shop_billing (shop_billing_data_id),
                    PRIMARY KEY(id),
                    CONSTRAINT FK_settings_channel FOREIGN KEY (channel_id) REFERENCES sylius_channel (id) ON DELETE CASCADE,
                    CONSTRAINT FK_settings_std_tpl FOREIGN KEY (standard_template_id) REFERENCES clearis_invoicing_template (id) ON DELETE SET NULL,
                    CONSTRAINT FK_settings_rect_tpl FOREIGN KEY (rectifying_template_id) REFERENCES clearis_invoicing_template (id) ON DELETE SET NULL,
                    CONSTRAINT FK_settings_std_series FOREIGN KEY (standard_series_id) REFERENCES clearis_invoicing_series (id) ON DELETE SET NULL,
                    CONSTRAINT FK_settings_rect_series FOREIGN KEY (rectifying_series_id) REFERENCES clearis_invoicing_series (id) ON DELETE SET NULL,
                    CONSTRAINT FK_settings_shop_billing FOREIGN KEY (shop_billing_data_id) REFERENCES clearis_invoicing_shop_billing_data (id) ON DELETE SET NULL
                ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
            SQL);
        }
    }

    public function down(Schema $schema): void
    {
        // Drop in dependency order.
        foreach ([
            'clearis_invoicing_channel_settings',
            'clearis_invoicing_invoice_tax_item',
            'clearis_invoicing_invoice_line_item',
            'clearis_invoicing_invoice',
            'clearis_invoicing_template',
            'clearis_invoicing_series',
            'clearis_invoicing_shop_billing_data',
            'clearis_invoicing_billing_data',
        ] as $table) {
            if ($schema->hasTable($table)) {
                $this->addSql(sprintf('DROP TABLE %s', $table));
            }
        }

        if ($schema->hasTable('sylius_address') && $schema->getTable('sylius_address')->hasColumn('tax_id')) {
            $this->addSql('ALTER TABLE sylius_address DROP COLUMN tax_id');
        }
    }
}
