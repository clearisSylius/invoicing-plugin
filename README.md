# Clearis Sylius Invoicing Plugin

`clearissylius/invoicing-plugin` is a full **replacement** for the official
`sylius/invoicing-plugin`, built for Spanish-fiscal-compliant Sylius 2 shops.

It ships:

- **Configurable PDF templates** per channel (Dompdf, all PHP, no binaries).
- **Per-channel numbering** with separate series for standard and rectifying
  invoices, atomic concurrent emission, optional yearly reset.
- **Spanish NIF/CIF/NIE** on every Sylius Address (optional), with the real
  control-digit algorithms.
- **Rectifying invoices** (factura rectificativa) with their own series and
  the four RD 1619/2012 reason codes (R1/R2/R3/R4 + R5 simplified).
- **Libro registro de facturas emitidas** Excel export with the canonical
  Spanish VAT breakdown (0/4/10/21/Otros) and rectifying flag.
- **Per-channel trigger**: emit on payment completed, on order shipped, on
  order completed, or manually.
- **Migration command** from `sylius/invoicing-plugin` that preserves
  original numbers and supports a multi-day coexistence window.
- **Public domain events** (`InvoiceIssuedEvent`, `InvoiceRectifiedEvent`,
  `InvoiceCancelledEvent`) so third-party plugins (future VeriFactu / SII /
  TicketBAI integrations) can hook in cleanly.

## Status

Early development. Not yet released. See `clearis-invoicing-plugin-plan.md`
for the development plan and `docs/migration-from-official.md` for the
cutover playbook.

## Install

See [`docs/installation.md`](docs/installation.md).

## Migrate from `sylius/invoicing-plugin`

See [`docs/migration-from-official.md`](docs/migration-from-official.md).

## Configure

See [`docs/configuration.md`](docs/configuration.md).

## Architecture

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
   ▼   (atomic SELECT…FOR UPDATE)
InvoiceNumberGenerator → InvoiceSeries (per channel + type)
   │
   ▼
Invoice (immutable) + LineItems + TaxItems
   │
   ▼
InvoicePdfGenerator → Dompdf → FilesystemPdfStorage
   │
   ▼
InvoiceIssuedEvent → InvoiceMailer → customer email
                  → (3rd-party listeners: VeriFactu, SII, …)
```

For rectifying invoices the path is the same but driven by
`RectifyingInvoiceFactory` and the RECTIFYING series.

## License

MIT.
