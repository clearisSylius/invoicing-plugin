<?php

declare(strict_types=1);

/**
 * Admin twig hooks contributed by the plugin.
 *
 * Returned as a PHP array (rather than YAML) because the Extension consumes
 * it via `prepend()` and Symfony's PHP loader is the most direct way to feed
 * a complex hierarchical config into `prependExtensionConfig`.
 *
 * The hook names correspond to slots exposed by Sylius Admin shared templates;
 * verify availability with `bin/console sylius:debug:twig-hooks`.
 */

return [
    // Inject "Facturas" block on the order show page.
    'sylius_admin.order.show.content.sections#left' => [
        'clearis_invoices' => [
            'template' => '@ClearisSyliusInvoicingPlugin/admin/order/_invoices_block.html.twig',
            'priority' => 50,
        ],
    ],

    // Top action: "Emitir factura manualmente" on order show.
    'sylius_admin.order.show.content.header.title_block.actions' => [
        'clearis_issue_invoice' => [
            'template' => '@ClearisSyliusInvoicingPlugin/admin/order/_issue_invoice_button.html.twig',
            'priority' => 100,
        ],
    ],

    // ---------------------------------------------------------------------
    // Hooks de nuestro recurso Invoice.
    //
    // El CRUD index/show de Sylius 2 emite hookables bajo
    // `sylius_admin.<singularName>.<action>.*`, donde `singularName` es la
    // parte después del punto en el alias del recurso. Para nuestro alias
    // `clearis_invoicing.invoice` el singular es `invoice`, así que los
    // hookables vienen como `sylius_admin.invoice.index.*` y
    // `sylius_admin.invoice.show.*`.
    //
    // Mientras estaba instalado `sylius/invoicing-plugin` (alias
    // `sylius_invoicing.invoice`, también singular `invoice`) había
    // colisión: los hooks que ELLOS registraban se mezclaban con los
    // nuestros. Con el oficial desinstalado el namespace `invoice` es
    // exclusivamente nuestro.
    //
    // Si en el futuro un tercer plugin volviera a usar singular `invoice`,
    // habría que renombrar nuestro alias (`clearis_invoicing.clearis_invoice`)
    // para volver a aislar el namespace.
    // ---------------------------------------------------------------------

    // Nota: el botón "Exportar libro registro" lo declara el grid en
    // `config/grids/invoice.yaml` como `actions.main.export_book`. Si se
    // añade también por hookable aparece duplicado, así que NO registramos
    // hook aquí — el partial `_export_book_button.html.twig` se conserva
    // por compatibilidad para quien quiera enchufarlo en otro sitio.

    // Body de la página show: nuestro partial con la cabecera de la factura,
    // datos del cliente, items y totales. El chrome (sidebar, navbar,
    // breadcrumb, title_block) lo aporta `@SyliusAdmin/shared/crud/show.html.twig`.
    'sylius_admin.invoice.show.content' => [
        'invoice_body' => [
            'template' => '@ClearisSyliusInvoicingPlugin/admin/invoice/show.html.twig',
            'priority' => 0,
        ],
    ],

    // NOTA: NO inyectamos un hook en `sylius_admin.channel.*` para mostrar
    // el bloque "Configuración de facturación" porque:
    //   1. Sylius 2 admin no tiene página `show` de canales, solo update,
    //      así que el hook `sylius_admin.channel.show.*` que probábamos
    //      antes nunca disparaba.
    //   2. El nombre exacto del slot en la página de update de canal varía
    //      entre versiones de `sylius/admin-ui`. Engancharnos a uno
    //      concreto haría el plugin frágil ante actualizaciones menores.
    //
    // En su lugar, exponemos el listado "Configuración por canal" como
    // entrada propia en el menú lateral del plugin (ver `AdminMenuListener`)
    // y desde ahí el admin gestiona la config de cada canal. La plantilla
    // `_invoicing_settings.html.twig` se conserva por si en el futuro
    // queremos enchufarla a un hook concreto del channel update page.
];
