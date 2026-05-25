<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migration;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceSeriesRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Entity\BillingData;
use ClearisSylius\InvoicingPlugin\Entity\Invoice;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceLineItem;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceSeries;
use ClearisSylius\InvoicingPlugin\Entity\InvoiceTaxItem;
use ClearisSylius\InvoicingPlugin\Entity\ShopBillingData;
use ClearisSylius\InvoicingPlugin\Model\InvoiceStateEnum;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;

/**
 * Reads invoices from the official `sylius/invoicing-plugin` and writes them
 * into our schema, preserving the original numbers.
 *
 * Strategy:
 *   - Read with raw SQL (works for both PATH_ORM and PATH_SQL — the ORM-only
 *     path would let us use the official repos, but SQL is simpler and we
 *     don't need ORM features here).
 *   - Idempotent: indexed by `legacyId`. Re-running the import skips already
 *     imported rows.
 *   - Series resolution: groups invoices by best-effort prefix detection
 *     (regex `^(.+?)(\d+)$`) and channel, creates an inactive series on first
 *     sighting, then after the import marks each as active=true so future
 *     emissions continue the numbering.
 *
 * The actual command and progress reporting live in
 * `Command\ImportFromOfficialCommand`; this class is the engine.
 */
final class LegacyInvoiceImporter
{
    /** @var array<string, int> map of "channel:prefix" → InvoiceSeries id (created during this run) */
    private array $seriesByGroup = [];

    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityManagerInterface $entityManager,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceSeriesRepositoryInterface $seriesRepository,
        private readonly OrderRepositoryInterface $orderRepository,
    ) {
    }

    /**
     * @return \Generator<ImportReportEntry>  yields one entry per invoice processed.
     */
    public function import(?\DateTimeInterface $fromDate, ?string $channelCode, int $batchSize, bool $dryRun): \Generator
    {
        $rows = $this->fetchInvoiceRows($fromDate, $channelCode);

        $batch = 0;
        foreach ($rows as $row) {
            $report = $this->importOne($row, $dryRun);
            yield $report;

            if (!$dryRun) {
                if (++$batch >= $batchSize) {
                    $this->entityManager->flush();
                    $this->entityManager->clear();
                    $batch = 0;
                }
            }
        }

        if (!$dryRun && $batch > 0) {
            $this->entityManager->flush();
        }

        if (!$dryRun) {
            $this->finaliseSeriesActivation();
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function importOne(array $row, bool $dryRun): ImportReportEntry
    {
        // El oficial 2.x usa UUID como PK del invoice (`varchar(255)`).
        // Lo tratamos como string desde aquí abajo.
        $legacyId = (string) $row['id'];
        $number = (string) $row['number'];

        if ($this->invoiceRepository->findByLegacyId($legacyId) !== null) {
            return new ImportReportEntry($legacyId, $number, ImportReportEntry::STATUS_SKIPPED, 'Already imported.');
        }

        $order = $this->orderRepository->find((int) $row['order_id']);
        if (!$order instanceof OrderInterface) {
            return new ImportReportEntry($legacyId, $number, ImportReportEntry::STATUS_WARNING, 'Order missing.');
        }
        $channel = $order->getChannel();
        if (!$channel instanceof ChannelInterface) {
            return new ImportReportEntry($legacyId, $number, ImportReportEntry::STATUS_WARNING, 'Channel missing on order.');
        }

        $billingData = $this->buildBillingData((int) $row['billing_data_id']);
        $shopBillingData = $this->buildShopBillingData((int) $row['shop_billing_data_id']);
        $series = $this->resolveOrCreateSeries($channel, $number);

        // El oficial 2.x no almacena `subtotal` ni `taxes_total` en
        // `sylius_invoicing_plugin_invoice` (los calcula la entidad a partir
        // de la colección de tax_items). Reconstruimos `taxes_total`
        // sumando `amount` de `sylius_invoicing_plugin_tax_item` para la
        // factura, y `subtotal` por diferencia con `total`.
        $taxesTotal = (int) ($row['taxes_total'] ?? $this->sumLegacyTaxesTotal($legacyId));
        $total = (int) $row['total'];
        $subtotal = (int) ($row['subtotal'] ?? ($total - $taxesTotal));

        $invoice = new Invoice();
        $invoice->initialise(
            type: InvoiceTypeEnum::STANDARD,
            number: $number,
            series: $series,
            order: $order,
            channel: $channel,
            currencyCode: (string) $row['currency_code'],
            localeCode: (string) ($row['locale_code'] ?? 'es_ES'),
            billingData: $billingData,
            shopBillingData: $shopBillingData,
            subtotal: $subtotal,
            taxesTotal: $taxesTotal,
            total: $total,
            paymentState: (string) ($row['payment_state'] ?? 'pending'),
            legacyId: $legacyId,
            issuedAt: new \DateTimeImmutable((string) $row['issued_at']),
        );
        $invoice->setState(InvoiceStateEnum::ISSUED);

        // Copia de line items (invoice_id en el oficial es VARCHAR(255),
        // referencia al UUID de la factura).
        $lineRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM sylius_invoicing_plugin_line_item WHERE invoice_id = ?',
            [$legacyId],
        );
        foreach ($lineRows as $lineRow) {
            $line = new InvoiceLineItem();
            $line->initialise(
                name: (string) $lineRow['name'],
                variantName: $lineRow['variant_name'] !== null ? (string) $lineRow['variant_name'] : null,
                variantCode: $lineRow['variant_code'] !== null ? (string) $lineRow['variant_code'] : null,
                quantity: (int) $lineRow['quantity'],
                unitPrice: (int) $lineRow['unit_price'],
                discountedUnitNetPrice: (int) ($lineRow['discounted_unit_net_price'] ?? $lineRow['unit_price']),
                subtotal: (int) $lineRow['subtotal'],
                // El oficial 2.x guarda `tax_rate` como string con `%` (ej. "21%").
                // El plugin nuevo asume número limpio ("21" o "21.5"); el template
                // añade el `%` al renderizar. Sin normalizar, en pantalla se ve
                // duplicado ("21%%"). Stripeamos espacios + `%` aquí mismo.
                taxRate: $this->normalizeLegacyTaxRate($lineRow['tax_rate']),
                taxTotal: (int) $lineRow['tax_total'],
                total: (int) $lineRow['total'],
            );
            $invoice->addLineItem($line);
        }

        // Copia de tax items. El oficial 2.x SOLO almacena `label` y `amount`
        // (céntimos). Para reconstruir la `base` (importe neto sobre el que
        // se aplica el IVA) preferimos sumar `subtotal` de los line_items
        // que comparten la misma rate — exacto al céntimo. Solo si no hay
        // line items que matcheen (caso raro: tax item sin equivalente),
        // caemos en la fórmula `amount * 100 / rate`, que arrastra el
        // redondeo del oficial.
        $netByRate = $this->aggregateLineNetByRate($lineRows);

        $taxRows = $this->connection->fetchAllAssociative(
            'SELECT * FROM sylius_invoicing_plugin_tax_item WHERE invoice_id = ?',
            [$legacyId],
        );
        foreach ($taxRows as $taxRow) {
            $rate = $this->extractRate((string) $taxRow['label']);
            $amount = (int) $taxRow['amount'];

            $rateKey = (string) number_format($rate, 2, '.', '');
            if (isset($netByRate[$rateKey])) {
                $base = $netByRate[$rateKey];
            } elseif ($rate > 0.0) {
                $base = (int) round($amount * 100 / $rate);
            } else {
                $base = (int) ($taxRow['base_amount'] ?? 0);
            }

            $tax = new InvoiceTaxItem();
            $tax->initialise(
                label: (string) $taxRow['label'],
                rate: number_format($rate, 2, '.', ''),
                base: $base,
                amount: $amount,
            );
            $invoice->addTaxItem($tax);
        }

        if (!$dryRun) {
            $this->entityManager->persist($invoice);
        }

        return new ImportReportEntry($legacyId, $invoice->getNumber(), ImportReportEntry::STATUS_IMPORTED);
    }

    /**
     * @return \Generator<array<string, mixed>>
     */
    private function fetchInvoiceRows(?\DateTimeInterface $fromDate, ?string $channelCode): \Generator
    {
        $sql = 'SELECT i.* FROM sylius_invoicing_plugin_invoice i';
        $params = [];
        $wheres = [];

        if ($channelCode !== null) {
            $sql .= ' INNER JOIN sylius_channel c ON c.id = i.channel_id';
            $wheres[] = 'c.code = ?';
            $params[] = $channelCode;
        }
        if ($fromDate !== null) {
            $wheres[] = 'i.issued_at >= ?';
            $params[] = $fromDate->format('Y-m-d H:i:s');
        }
        if ($wheres !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }
        $sql .= ' ORDER BY i.issued_at ASC, i.id ASC';

        $stmt = $this->connection->executeQuery($sql, $params);
        while (($row = $stmt->fetchAssociative()) !== false) {
            yield $row;
        }
    }

    private function buildBillingData(int $billingDataId): BillingData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM sylius_invoicing_plugin_billing_data WHERE id = ?',
            [$billingDataId],
        );
        if ($row === false) {
            throw new \RuntimeException(sprintf('Legacy BillingData #%d not found.', $billingDataId));
        }

        $billing = new BillingData();
        $billing->initialise(
            firstName: $row['first_name'] !== null ? (string) $row['first_name'] : null,
            lastName: $row['last_name'] !== null ? (string) $row['last_name'] : null,
            company: $row['company'] !== null ? (string) $row['company'] : null,
            // En el oficial 2.x el NIF/CIF vive en la columna `nif`
            // (varchar(20) nullable); no hay `tax_id` en billing_data.
            taxId: $row['nif'] !== null ? (string) $row['nif'] : null,
            street: (string) $row['street'],
            city: (string) $row['city'],
            postcode: (string) $row['postcode'],
            provinceCode: $row['province_code'] !== null ? (string) $row['province_code'] : null,
            provinceName: $row['province_name'] !== null ? (string) $row['province_name'] : null,
            countryCode: (string) $row['country_code'],
        );

        return $billing;
    }

    private function buildShopBillingData(int $shopBillingDataId): ShopBillingData
    {
        $row = $this->connection->fetchAssociative(
            'SELECT * FROM sylius_invoicing_plugin_shop_billing_data WHERE id = ?',
            [$shopBillingDataId],
        );
        if ($row === false) {
            throw new \RuntimeException(sprintf('Legacy ShopBillingData #%d not found.', $shopBillingDataId));
        }

        // En el oficial 2.x la tabla shop_billing_data tiene `company` (no
        // `company_name`), `tax_id` y `representative` además de la
        // dirección postal. Mapeamos `company` → companyName.
        $shop = new ShopBillingData();
        $shop->initialise(
            companyName: (string) ($row['company'] ?? ''),
            taxId: (string) ($row['tax_id'] ?? ''),
            street: (string) ($row['street'] ?? ''),
            city: (string) ($row['city'] ?? ''),
            postcode: (string) ($row['postcode'] ?? ''),
            countryCode: (string) ($row['country_code'] ?? 'ES'),
        );

        return $shop;
    }

    /**
     * Detect the prefix of an imported number (everything before the trailing
     * digit run) and either find or create an InvoiceSeries for that
     * channel + prefix. The created series is inserted INACTIVE so it doesn't
     * fight with whatever the admin configures separately during the
     * convivencia window.
     */
    private function resolveOrCreateSeries(ChannelInterface $channel, string $number): InvoiceSeries
    {
        $prefix = $this->extractPrefix($number);
        $groupKey = sprintf('%d:%s', $channel->getId(), $prefix);

        if (isset($this->seriesByGroup[$groupKey])) {
            /** @var InvoiceSeries $series */
            $series = $this->entityManager->find(InvoiceSeries::class, $this->seriesByGroup[$groupKey]);

            return $series;
        }

        $code = sprintf('legacy-%s-%s', (string) $channel->getCode(), $prefix !== '' ? $prefix : 'main');
        $existing = $this->seriesRepository->findOneByCode($code);
        if ($existing instanceof InvoiceSeries) {
            $this->seriesByGroup[$groupKey] = (int) $existing->getId();

            return $existing;
        }

        $series = new InvoiceSeries();
        $series->setCode($code);
        $series->setName(sprintf('Importada del oficial (%s)', $prefix !== '' ? $prefix : 'principal'));
        $series->setChannel($channel);
        $series->setType(InvoiceTypeEnum::STANDARD);
        $series->setFormat($this->buildFormatFromPrefix($prefix));
        $series->setPadding($this->extractPadding($number));
        $series->setCurrentNumber(0); // will be reseeded at finaliseSeriesActivation()
        $series->setActive(false);
        $series->setYearlyReset(false);

        $this->entityManager->persist($series);
        $this->entityManager->flush();

        $this->seriesByGroup[$groupKey] = (int) $series->getId();

        return $series;
    }

    /**
     * After every legacy invoice is imported, walk the new series we created
     * and set their `currentNumber` to the MAX numeric suffix imported, then
     * mark them active so subsequent emissions continue the sequence.
     */
    private function finaliseSeriesActivation(): void
    {
        foreach ($this->seriesByGroup as $seriesId) {
            /** @var InvoiceSeries|null $series */
            $series = $this->entityManager->find(InvoiceSeries::class, $seriesId);
            if ($series === null) {
                continue;
            }

            $maxNumber = $this->invoiceRepository->findMaxNumberForSeries($seriesId);
            $counter = $maxNumber !== null ? $this->extractCounter($maxNumber) : 0;

            $series->setCurrentNumber($counter);
            $series->setActive(true);
        }

        $this->entityManager->flush();
    }

    private function extractPrefix(string $number): string
    {
        if (preg_match('/^(.*?)(\d+)$/', $number, $matches) === 1) {
            return $matches[1];
        }

        return '';
    }

    private function extractCounter(string $number): int
    {
        if (preg_match('/(\d+)$/', $number, $matches) === 1) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function extractPadding(string $number): int
    {
        if (preg_match('/(\d+)$/', $number, $matches) === 1) {
            return strlen($matches[1]);
        }

        return 4;
    }

    private function buildFormatFromPrefix(string $prefix): string
    {
        // If the prefix contains 4 digits that look like a year, treat that
        // segment as a placeholder for {year}.
        $format = preg_replace('/\b(20\d{2})\b/', '{year}', $prefix) ?? $prefix;

        return $format . '{number}';
    }

    private function extractRate(string $label): float
    {
        if (preg_match('/(\d+(?:\.\d+)?)/', $label, $matches) === 1) {
            return (float) $matches[1];
        }

        return 0.0;
    }

    /**
     * Suma `amount` de todos los tax_items del invoice legacy.
     *
     * El oficial 2.x no expone `taxes_total` como columna en
     * `sylius_invoicing_plugin_invoice` (lo calcula la entidad desde la
     * colección de TaxItems). Recuperamos el agregado leyendo
     * `sylius_invoicing_plugin_tax_item` (cuyo `invoice_id` es el UUID
     * de la factura).
     */
    /**
     * Agrupa los `subtotal` (neto en céntimos) de las filas de line_item
     * por su `tax_rate` normalizada (string con dos decimales: "21.00", "10.00").
     * Resultado: ["21.00" => 86900, "10.00" => 4500, …]
     *
     * Sirve para reconstruir el `base` exacto de cada InvoiceTaxItem
     * sumando los nets de los items que pagan esa rate, sin pasar por la
     * división `amount / rate` que arrastra redondeos.
     *
     * @param array<int, array<string, mixed>> $lineRows  filas crudas
     *
     * @return array<int|string, int>  PHPStan se ve obligado a admitir int|string
     *                                 porque PHP castea claves string-numéricas
     *                                 canónicas a int. En la práctica las claves
     *                                 aquí son "21.00", "10.00"... que NO son
     *                                 canónicas, así que siempre vienen como
     *                                 string, pero el sistema de tipos no
     *                                 puede asegurarlo.
     */
    private function aggregateLineNetByRate(array $lineRows): array
    {
        $out = [];
        foreach ($lineRows as $row) {
            $rawRate = $row['tax_rate'] ?? null;
            if ($rawRate === null) {
                continue;
            }
            $cleanRate = $this->normalizeLegacyTaxRate($rawRate);
            if ($cleanRate === null || !is_numeric($cleanRate)) {
                continue;
            }
            $key = number_format((float) $cleanRate, 2, '.', '');
            $out[$key] = ($out[$key] ?? 0) + (int) $row['subtotal'];
        }

        return $out;
    }

    /**
     * Normaliza el valor de `tax_rate` que viene de la BD legacy. El oficial
     * lo guarda como string libre (suele ser "21%", "10%", "4%"). Devolvemos
     * sólo el número como string para que el template lo renderice sin que
     * se duplique el símbolo.
     */
    private function normalizeLegacyTaxRate(mixed $rawTaxRate): ?string
    {
        if ($rawTaxRate === null) {
            return null;
        }

        $clean = trim((string) $rawTaxRate);
        $clean = rtrim($clean, '%');
        $clean = trim($clean);

        if ($clean === '') {
            return null;
        }

        // Si es numérico, recortamos ceros decimales innecesarios ("21.00" → "21").
        if (is_numeric($clean)) {
            $float = (float) $clean;
            $clean = rtrim(rtrim(number_format($float, 2, '.', ''), '0'), '.');
        }

        return $clean;
    }

    private function sumLegacyTaxesTotal(string $legacyInvoiceId): int
    {
        $sum = $this->connection->fetchOne(
            'SELECT COALESCE(SUM(amount), 0) FROM sylius_invoicing_plugin_tax_item WHERE invoice_id = ?',
            [$legacyInvoiceId],
        );

        return (int) $sum;
    }
}
