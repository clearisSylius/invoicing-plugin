# Migración desde `sylius/invoicing-plugin`

El plugin admite **convivencia temporal** con el oficial. El objetivo es que puedas instalar `clearissylius/invoicing-plugin` al lado del oficial, importar el histórico, validar durante varios días, y solo entonces hacer el cutover definitivo. Sin downtime, sin reescritura de número de factura, sin pérdida de PDFs antiguos.

## Paso 1 — Día 0: instalación en modo dual

Instala el paquete sin tocar el oficial:

```bash
composer require clearissylius/invoicing-plugin
```

Registra el bundle (junto al de Sylius, dejas también el oficial registrado).

```php
// config/bundles.php
return [
    Sylius\InvoicingPlugin\SyliusInvoicingPlugin::class => ['all' => true],
    ClearisSylius\InvoicingPlugin\ClearisSyliusInvoicingPlugin::class => ['all' => true],
];
```

Activa explícitamente el `legacy_mode` para que NO se generen facturas duplicadas:

```yaml
# config/packages/clearis_sylius_invoicing.yaml
clearis_sylius_invoicing:
    legacy_mode: true   # CRÍTICO
```

```bash
bin/console doctrine:migrations:migrate
```

En este punto:

- Las tablas `clearis_invoicing_*` están creadas y vacías.
- El oficial sigue emitiendo facturas como siempre.
- El admin del nuestro está disponible pero no compite por nuevas emisiones (los listeners están en cortocircuito por `legacy_mode=true`).

## Paso 2 — Día 0–1: configuración inicial

Desde admin:

1. **Series** (`/admin/invoicing-series/`): crea una serie `STANDARD` y otra `RECTIFYING` por canal. Si vas a respetar la numeración del oficial, **deja el contador a 0**: el importador lo ajustará al MAX importado al terminar.
2. **Plantillas** (`/admin/invoicing-templates/`): crea una plantilla por defecto (puedes usar la que viene en el plugin) y, si tienes más de un canal, una específica por canal con su logo.
3. **Configuración del canal** (en el show del canal de Sylius, hay un bloque nuevo "Configuración de facturación"): trigger, series, plantillas, datos fiscales del emisor.

## Paso 3 — Día 1–2: importación de histórico

Primero, dry-run para revisar:

```bash
bin/console clearis:invoicing:import-from-official --dry-run -v
```

La salida lista cada factura del oficial con su estado: `imported`, `skipped` (ya estaba), `warning`. Comprueba que el total cuadra con el grid del oficial.

Si todo bien, ejecuta para de verdad:

```bash
bin/console clearis:invoicing:import-from-official --batch=500
```

Tras la importación:

- Cada factura del oficial existe ahora en `clearis_invoicing_invoice` con su `number` original.
- Las `InvoiceSeries` que el importador creó automáticamente quedan **activas** con `currentNumber = MAX` importado, listas para continuar.
- Los PDFs antiguos se mantienen donde estaban. El controlador `DownloadInvoicePdfController` los regenerará bajo demanda con la plantilla actual si no encuentra un PDF emitido.

Opciones útiles:

- `--from-date=2026-01-01`: limita el rango (útil para probar con un trimestre antes de migrar todo).
- `--channel=ES`: limita a un canal concreto.

## Paso 4 — Día 2 a N: convivencia

Periodo de validación. Durante varios días:

- El oficial sigue emitiendo las facturas nuevas.
- En `/admin/clearis-invoices/` puedes ver el histórico tal y como lo dejó la importación.
- Genera el libro registro desde el nuestro y compáralo con el del oficial, si el cliente lo tenía. Las totales por trimestre tienen que cuadrar.
- En un canal de **staging** puedes bajar `legacy_mode` temporalmente para probar la emisión real con un pedido de prueba sin afectar producción.

## Paso 5 — Día N: cutover

Cuando el cliente está satisfecho, en un solo deploy:

```yaml
# config/packages/clearis_sylius_invoicing.yaml
clearis_sylius_invoicing:
    legacy_mode: false
```

Además, **silencia los listeners del oficial** para que no siga generando. La forma más limpia es decorar sus servicios con un no-op:

```yaml
# config/services.yaml — durante la fase de convivencia o tras el cutover
services:
    Sylius\InvoicingPlugin\EventListener\SendInvoiceEmailListener:
        class: ClearisSylius\InvoicingPlugin\Migration\NoopListener  # ejemplo
        decorates: 'sylius_invoicing.event_listener.send_invoice_email'
```

Tras el deploy:

- Las facturas nuevas las emite **solo** nuestro plugin.
- El admin del oficial sigue navegable (las tablas siguen ahí) — útil para consultar las viejas con su look-and-feel original.

## Paso 6 — Día N+M: desinstalación del oficial

Tras unos días sin incidencias:

```bash
composer remove sylius/invoicing-plugin
```

Elimina el bundle de `config/bundles.php`. Las tablas `sylius_invoicing_plugin_*` permanecen en la BD; el cliente decide si las elimina como backup o las conserva. Un script SQL opcional las elimina cuando quiera.

## Rollback en cada paso

| Antes de | Cómo revertir |
|----------|---------------|
| Paso 3   | No hay nada nuevo en `clearis_*`. Truncate tablas si quieres dejarlo limpio. |
| Paso 4   | Las tablas importadas son irrelevantes mientras `legacy_mode=true`. |
| Paso 5   | Vuelve `legacy_mode` a `true`. Restaura los listeners del oficial. Aparecerán algunas facturas duplicadas que habrá que limpiar manualmente, pero no se pierde nada. |
| Paso 6   | Reinstalar el oficial. Las tablas `sylius_invoicing_plugin_*` siguen ahí, así que el oficial recupera todo su histórico. |
