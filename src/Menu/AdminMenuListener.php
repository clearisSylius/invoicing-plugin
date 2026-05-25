<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Menu;

use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * Add a "Facturación" section to the admin sidebar.
 *
 * We hook into `sylius.menu.admin.main` (the canonical KnpMenu event Sylius 2
 * still fires for its main admin nav). The section is inserted between the
 * existing "Catalog" and "Customers" sections by giving it explicit position
 * via `setExtra('priority')`, but ordering is ultimately determined by Sylius
 * — we don't enforce a hard position, only group our items together.
 *
 * Why an event listener rather than the new MenuBuilder decorator pattern:
 * the decorator approach replaces the *entire* root menu, which would mean
 * recreating every other admin item just to add ours. The event listener
 * lets us additively extend without coupling to the rest of the admin.
 */
final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        // Sylius admin-ui v0.9.1 renderiza los iconos del sidebar con
        // `symfony/ux-icons`, que exige el formato `prefix:icon` (no el
        // CSS class `ti ti-...`). El prefijo `tabler:` es el namespace
        // configurado por Sylius. Solo usamos nombres que ya aparecen en
        // el propio menú admin de Sylius — así nos garantizamos que los
        // SVG están cargados en esta instalación.
        $invoicing = $menu
            ->addChild('clearis_invoicing', ['route' => 'clearis_invoicing_admin_invoice_index'])
            ->setLabel('clearis.ui.menu.invoicing')
            ->setLabelAttribute('icon', 'tabler:certificate')
        ;

        $invoicing
            ->addChild('clearis_invoicing_invoices', ['route' => 'clearis_invoicing_admin_invoice_index'])
            ->setLabel('clearis.ui.menu.invoices')
            ->setLabelAttribute('icon', 'tabler:list-details')
        ;

        $invoicing
            ->addChild('clearis_invoicing_series', ['route' => 'clearis_invoicing_admin_invoice_series_index'])
            ->setLabel('clearis.ui.menu.series')
            ->setLabelAttribute('icon', 'tabler:layout-list')
        ;

        $invoicing
            ->addChild('clearis_invoicing_templates', ['route' => 'clearis_invoicing_admin_invoice_template_index'])
            ->setLabel('clearis.ui.menu.templates')
            ->setLabelAttribute('icon', 'tabler:bookmark')
        ;

        $invoicing
            ->addChild('clearis_invoicing_export_book', ['route' => 'clearis_invoicing_admin_invoice_export_book'])
            ->setLabel('clearis.ui.menu.export_book')
            ->setLabelAttribute('icon', 'tabler:archive')
        ;

        $invoicing
            ->addChild('clearis_invoicing_channels', ['route' => 'clearis_invoicing_admin_channels_list'])
            ->setLabel('clearis.ui.menu.channels_config')
            ->setLabelAttribute('icon', 'tabler:settings')
        ;
    }
}
