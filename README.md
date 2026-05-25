# Clearis Sylius Invoicing Plugin

[![License](https://img.shields.io/packagist/l/clearissylius/invoicing-plugin.svg)](https://packagist.org/packages/clearissylius/invoicing-plugin)
[![Latest Stable Version](https://img.shields.io/packagist/v/clearissylius/invoicing-plugin.svg)](https://packagist.org/packages/clearissylius/invoicing-plugin)
[![Total Downloads](https://img.shields.io/packagist/dt/clearissylius/invoicing-plugin.svg)](https://packagist.org/packages/clearissylius/invoicing-plugin)
[![Build](https://github.com/clearisSylius/invoicing-plugin/actions/workflows/build.yaml/badge.svg)](https://github.com/clearisSylius/invoicing-plugin/actions/workflows/build.yaml)

A full **replacement** for the official `sylius/invoicing-plugin`, designed
for Sylius 2 shops that need to comply with Spanish fiscal regulations (RD
1619/2012, AEAT *libro registro de facturas emitidas*, modelo 303/390). It
also works perfectly well for shops outside Spain that simply want a
configurable, modern invoicing layer.

This plugin coexists with the official plugin during a transition window
(see the migration command below) and ships a clean public event API so
future VeriFactu / SII / TicketBAI integrations can hook in without forking
the codebase.

## What you get

- **Configurable PDF templates per channel**. Pure PHP rendering with
  [Dompdf](https://github.com/dompdf/dompdf), no external binaries, no
  Wkhtmltopdf. Per-channel templates for both standard and rectifying
  invoices, with optional accent colour and shop logo per template.
- **Per-channel numbering** with separate series for standard and
  rectifying invoices. Atomic emission under concurrent load
  (`SELECT … FOR UPDATE`). Customisable format with `{year}`, `{number}`
  and `{prefix}` placeholders. Optional yearly counter reset.
- **Spanish NIF / CIF / NIE on every Sylius `Address`** (optional field),
  validated server-side with the real control-digit algorithms (8-digit
  DNI + letter, NIE prefix substitution X/Y/Z → 0/1/2, CIF Luhn-modified
  with org-type rules for P/Q/R/S/W/N/J vs. A/B/E/H).
- **Rectifying invoices** (*factura rectificativa*) with their own series
  and the five RD 1619/2012 reason codes (R1 / R2 / R3 / R4 / R5). Total
  rectifications counter-entry the original (cancellation); partial
  rectifications apply the supplied base/tax deltas only.
- **Libro registro de facturas emitidas** Excel export with the canonical
  Spanish VAT breakdown (base + cuota for 0 % / 4 % / 10 % / 21 % / Otros),
  rectifying flag, "Rectifica a" column, currency, channel and customer
  TaxID. Date-range and channel filters from the admin.
- **Configurable invoice trigger per channel**: emit on order completed,
  on payment completed, on order shipped, or manually from the order view.
- **Migration command** from `sylius/invoicing-plugin` that preserves the
  original invoice numbers, supports a multi-day coexistence window, and
  is idempotent (indexed by `legacyId`).
- **Public domain events** (`InvoiceIssuedEvent`,
  `InvoiceRectifiedEvent`, `InvoiceCancelledEvent`) so third-party plugins
  can listen and dispatch to VeriFactu, SII, TicketBAI, or any other tax
  agency integration without modifying this plugin.
- **Per-channel customer notification** that emails the issued (or
  rectifying) invoice to the customer with the PDF attached. Send-on-issue
  is a per-channel switch.

## Where to find each feature in the admin

Once the bundle is registered and the migrations are run, the admin gains
these entry points (all in Spanish translation by default; English
fallback shipped):

| Where | What you can do |
|---|---|
| **Sidebar → Facturación → Facturas** | List, filter and view all issued invoices. Download the PDF. Open the "Emitir rectificativa" modal from the show page. |
| **Sidebar → Facturación → Series** | CRUD over `InvoiceSeries`. One per channel + type (standard / rectifying). Configure format, padding, yearly reset, active flag. |
| **Sidebar → Facturación → Plantillas PDF** | CRUD over `InvoiceTemplate`. Standard or rectifying. Per template: accent colour, shop logo, optional custom Twig template path. |
| **Sidebar → Facturación → Configuración por canal** | Per-channel landing page. Each row shows config status (unconfigured / partial / complete) and a "Configurar" button. |
| **Channel admin → Order show → "Facturas" block** | List of invoices already emitted for that order. Manual issuance button when no invoice exists yet (and the channel is not in legacy mode). |
| **Invoice show → "Exportar libro registro"** | Top-right action that opens the date-range / channel form and streams the `.xlsx` directly. |
| **Customer address forms (front + admin)** | Optional `NIF/CIF/NIE` field. Validated only if filled. Empty value passes silently. |

## Requirements

- PHP 8.2 or newer
- Sylius 2.0 or newer
- Symfony 6.4 or 7.x
- Doctrine ORM 3.x
- A relational database with row-level locking (MySQL 8 / MariaDB 10.5+ /
  PostgreSQL 13+ all work — the numbering is `SELECT … FOR UPDATE`).

## Installation

```bash
composer require clearissylius/invoicing-plugin
```

Register the bundle in `config/bundles.php`:

```php
return [
    // ...
    ClearisSylius\InvoicingPlugin\ClearisSyliusInvoicingPlugin::class => ['all' => true],
];
```

Run the Doctrine migrations:

```bash
bin/console doctrine:migrations:migrate
```

Full installation walk-through (configuration file, services overrides,
running the test application) in [`docs/installation.md`](docs/installation.md).

## Configuration

Minimal `config/packages/clearis_sylius_invoicing.yaml`:

```yaml
clearis_sylius_invoicing:
    legacy_mode: false    # set to true while you still run sylius/invoicing-plugin in parallel
    pdf:
        default_template_code: default
        storage_directory: '%kernel.project_dir%/var/invoices'
```

All options (per-channel sender e-mail, fallback templates, admin URL
prefix override) documented in [`docs/configuration.md`](docs/configuration.md).

## Migrating from `sylius/invoicing-plugin`

The plugin is designed to **coexist** with the official one during a
multi-day cutover window. You install both, set `legacy_mode: true`, run
the importer in dry-run mode, validate, then flip the switch.

```bash
# Dry-run: shows what would be imported, no DB writes
bin/console clearis:invoicing:import-from-official --dry-run

# Real run with a date floor and channel filter
bin/console clearis:invoicing:import-from-official --from-date=2024-01-01 --channel=ES

# Once everything is imported and validated, flip legacy_mode to false in
# config and the plugin takes over invoice emission immediately.
```

Full migration playbook with rollback strategy in
[`docs/migration-from-official.md`](docs/migration-from-official.md).

## Public events

If you maintain or build a VeriFactu / SII / TicketBAI integration plugin,
listen for these events emitted from `ClearisSylius\InvoicingPlugin\Event`:

- `InvoiceIssuedEvent` — fired right after a new standard or rectifying
  invoice is persisted and its PDF rendered.
- `InvoiceRectifiedEvent` — fired when a rectifying invoice supersedes
  (totally or partially) an existing one.
- `InvoiceCancelledEvent` — fired when an invoice is set to the
  `CANCELLED` workflow place (typically by a total rectification).

All three carry the immutable `Invoice` snapshot, the original (for
rectifications), and timestamps. They are dispatched through the standard
Symfony `EventDispatcherInterface`, so wiring a listener is the usual
`#[AsEventListener]` attribute.

## Architecture overview

```
Order (Sylius)
   │
   ▼   (listener / manual button)
CreateInvoice [Messenger command]
   │
   ▼
InvoiceFactory ──── BillingDataSnapshotter ──── (Address with taxId)
   │                ShopBillingDataSnapshotter
   │
   ▼   (atomic SELECT … FOR UPDATE)
InvoiceNumberGenerator → InvoiceSeries (per channel + type)
   │
   ▼
Invoice (immutable) + LineItems + TaxItems
   │
   ▼
InvoicePdfGenerator → Dompdf → FilesystemPdfStorage
   │
   ▼
InvoiceIssuedEvent → InvoiceMailer → customer e-mail
                  → (3rd-party listeners: VeriFactu, SII, TicketBAI …)
```

Rectifying invoices follow the same flow but driven by
`RectifyingInvoiceFactory` and the rectifying series.

## Support

- **Issues**: [github.com/clearisSylius/invoicing-plugin/issues](https://github.com/clearisSylius/invoicing-plugin/issues)
- **Discussion / questions**: open an issue with the `question` label.
- **Security vulnerabilities**: do NOT open a public issue. E-mail
  `alejandro.rios@clearis.es` directly. See [`SECURITY.md`](SECURITY.md)
  for the responsible-disclosure policy.

## Contributing

Pull requests welcome. Before submitting:

1. The full quality suite must pass locally:
   ```bash
   vendor/bin/ecs check
   vendor/bin/phpstan analyse
   vendor/bin/phpunit
   ```
2. New behaviour must come with tests (Unit at minimum; Behat preferred
   for admin workflows).
3. PHPStan stays at `level: max`. If you genuinely cannot satisfy it,
   document why in the docblock and add a targeted `ignoreErrors` entry
   in `phpstan.neon`.

More in [`CONTRIBUTING.md`](CONTRIBUTING.md).

## License

[MIT](LICENSE) — © Clearis.

## Author

Maintained by [Clearis](https://clearis.es). For commercial support,
custom integrations, or fiscal-compliance consulting on Sylius shops,
e-mail `alejandro.rios@clearis.es`.
