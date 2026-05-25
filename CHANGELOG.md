# Changelog

All notable changes to `clearissylius/invoicing-plugin` are documented in
this file. The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Initial release scaffold. See feature list in the [README](README.md).
- Configurable PDF templates per channel via Dompdf.
- Per-channel atomic invoice numbering with optional yearly reset.
- Spanish NIF / CIF / NIE validation on customer addresses.
- Rectifying invoices with RD 1619/2012 reason codes (R1–R5).
- Libro registro Excel export with Spanish VAT breakdown.
- Per-channel emission trigger (manual / on order completed / on payment
  completed / on order shipped).
- Migration command from `sylius/invoicing-plugin` with dry-run, date
  filter and channel filter.
- Public domain events (`InvoiceIssuedEvent`, `InvoiceRectifiedEvent`,
  `InvoiceCancelledEvent`) for third-party integrations.
- Customer e-mail notification with PDF attached.

### Documented

- Installation, configuration, and migration guides in [`docs/`](docs/).
- Public events contract in the README.
- Security policy ([`SECURITY.md`](SECURITY.md)).
- Contribution guidelines ([`CONTRIBUTING.md`](CONTRIBUTING.md)).

[Unreleased]: https://github.com/clearisSylius/invoicing-plugin/commits/main
