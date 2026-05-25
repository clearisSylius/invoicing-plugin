<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Resolver;

/**
 * Single switch driving whether the plugin's auto-emission listeners fire.
 *
 * When `legacy_mode=true` (the default during installation, while the
 * official sylius/invoicing-plugin still handles new invoices), every
 * listener short-circuits and returns early. The plugin is fully installed
 * in admin (entities, grids, importer command) but does NOT compete with
 * the official plugin for new emissions.
 *
 * Flip the flag in `config/packages/clearis_sylius_invoicing.yaml` at cutover.
 */
final class LegacyModeChecker
{
    public function __construct(
        private readonly bool $legacyMode,
    ) {
    }

    public function isLegacyMode(): bool
    {
        return $this->legacyMode;
    }
}
