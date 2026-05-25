# Configuration reference

## `clearis_sylius_invoicing`

```yaml
clearis_sylius_invoicing:
    legacy_mode: false
    pdf:
        default_template_code: 'default'
        storage_directory: '%kernel.project_dir%/var/invoices'
        paper_size: 'A4'
        orientation: 'portrait'   # or landscape
    admin:
        invoice_path: 'clearis-invoices'
```

`legacy_mode` (bool, default `false`): when `true`, every plugin listener that
would emit an invoice in response to a Sylius event (`order_completed`,
`payment_completed`, `shipment_shipped`) short-circuits. The admin UI, the
import command and PDF regeneration remain functional. This is the safe
default during a migration window where `sylius/invoicing-plugin` is still
active.

`pdf.default_template_code`: code of the `InvoiceTemplate` used when no
channel-specific template is assigned and the matched type (standard /
rectifying) lookup falls through. If no row in `clearis_invoicing_template`
matches, the bundled Twig templates `@ClearisSyliusInvoicingPlugin/pdf/*.html.twig`
are used.

`pdf.storage_directory`: absolute path where generated PDFs are written.
`FilesystemPdfStorage` builds paths under `invoices/{year}/{series-code}/{number}.pdf`
inside this directory. Make sure the web user can write there.

`admin.invoice_path`: URL segment under `/%sylius_admin.path_name%/`. Defaults
to `clearis-invoices` to avoid colliding with `/admin/invoices/` (which is
where the official plugin mounts its grid). Once you finish the migration you
can change this to `invoices` if you prefer.

## Per-channel settings (admin)

Each channel has a one-to-one `ChannelInvoicingSettings`:

| Field | Effect |
|-------|--------|
| `trigger` | When to emit (`on_payment_completed`, `on_order_shipped`, `on_order_completed`, `manual`). |
| `sendEmailOnIssue` | When true, the customer receives the PDF by email on emission and on rectification. |
| `standardSeries` | The `InvoiceSeries` (type=STANDARD) the channel uses. |
| `rectifyingSeries` | The `InvoiceSeries` (type=RECTIFYING) used when emitting corrections. |
| `standardTemplate` | The `InvoiceTemplate` (type=STANDARD) used to render the PDF. |
| `rectifyingTemplate` | The `InvoiceTemplate` (type=RECTIFYING) — falls back to the standard one if null. |
| `shopBillingData` | Issuer's fiscal data (CIF, registered address). Snapshotted onto every invoice emitted from this channel. |

## Number format placeholders

`InvoiceSeries.format` accepts:

- `{year}`   — 4-digit emission year.
- `{number}` — zero-padded counter, width = `padding`.
- `{prefix}` — the series `code` (useful for letter prefixes).

Examples:

| Format | Result for n=42 in 2026 |
|--------|--------------------------|
| `{year}/{number}` (padding 4) | `2026/0042` |
| `F-{year}-{number}` (padding 5) | `F-2026-00042` |
| `R{year}{number}` (padding 4) | `R20260042` |

## Environment variables

`CLEARIS_INVOICING_SENDER` — email address used as `From:` for invoice
emails. Not required if you disable emails (per-channel
`sendEmailOnIssue=false`).
