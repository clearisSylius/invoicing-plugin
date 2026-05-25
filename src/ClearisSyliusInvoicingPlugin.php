<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * `clearissylius/invoicing-plugin`
 *
 * Full invoicing replacement for Sylius 2 with configurable PDF templates,
 * per-channel numbering, Spanish NIF/CIF on addresses, rectifying invoices,
 * libro registro Excel export and a migration path from sylius/invoicing-plugin.
 *
 * The bundle root is the package root, not src/, so config/, templates/ and
 * translations/ resolve out of the box.
 */
final class ClearisSyliusInvoicingPlugin extends Bundle
{
    use SyliusPluginTrait;

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
