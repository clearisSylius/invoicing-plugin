# Installation

Requirements: PHP ≥ 8.2, Sylius ≥ 2.0.

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

Add the plugin configuration in `config/packages/clearis_sylius_invoicing.yaml`:

```yaml
clearis_sylius_invoicing:
    legacy_mode: true  # set to false once you have cut over from sylius/invoicing-plugin
    pdf:
        default_template_code: default
        storage_directory: '%kernel.project_dir%/var/invoices'
    admin:
        invoice_path: clearis-invoices  # url segment under /admin/
```

Import the admin routes (e.g. in `config/routes/clearis_invoicing.yaml`):

```yaml
clearis_invoicing_admin:
    resource: '@ClearisSyliusInvoicingPlugin/config/routes/admin.yaml'
    prefix: /%sylius_admin.path_name%
```

Run migrations:

```bash
bin/console doctrine:migrations:migrate
```

Configure the bare minimum from admin before issuing your first invoice:

1. Create at least one `InvoiceSeries` per channel with `type = standard` and `active = true`.
2. Optionally create another series with `type = rectifying` if you intend to emit corrections.
3. Create at least one `InvoiceTemplate` (the bundled default works out of the box; you only need one row to assign it to a channel).
4. Create or edit a `ChannelInvoicingSettings` for each channel: pick the trigger, the series, the template, and fill in the shop billing data (CIF, registered address).
5. Set the environment variable `CLEARIS_INVOICING_SENDER` to the email address used as the `From:` for invoice emails — typically the issuer's billing address.

If you do NOT have `sylius/invoicing-plugin` installed, you're done — flip `legacy_mode: false` and the plugin will start emitting invoices on the trigger you configured per channel.

If you DO have the official plugin installed, read `docs/migration-from-official.md` next.
