<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Exporter;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Render the libro registro de facturas emitidas spreadsheet from our own
 * Invoice/TaxItem rows. One spreadsheet row per invoice with:
 *
 *  - Number, date, NIF/CIF, customer name, address
 *  - Base 0% / Cuota 0%
 *  - Base 4% / Cuota 4%
 *  - Base 10% / Cuota 10%
 *  - Base 21% / Cuota 21%
 *  - Base Otros / Cuota Otros (rates outside 0/4/10/21)
 *  - Base total, Cuota total, Total
 *  - Type ("F" / "R") and rectified reference (only on rectifying rows)
 *  - Channel
 *
 * Unlike the older plugin which queried Sylius items, this one walks our own
 * InvoiceTaxItem rows — which were aggregated at emission time and live on
 * the immutable snapshot, so the export is always self-consistent.
 */
final class InvoiceBookExporter implements InvoiceBookExporterInterface
{
    private const RATE_BUCKETS = ['0.00', '4.00', '10.00', '21.00'];

    private const RATE_OTHER = 'OTHER';

    private const HEADERS = [
        'A' => 'Nº Factura',
        'B' => 'Fecha',
        'C' => 'Tipo',
        'D' => 'NIF/CIF',
        'E' => 'Cliente',
        'F' => 'Dirección',
        'G' => 'Base 0%',
        'H' => 'Cuota 0%',
        'I' => 'Base 4%',
        'J' => 'Cuota 4%',
        'K' => 'Base 10%',
        'L' => 'Cuota 10%',
        'M' => 'Base 21%',
        'N' => 'Cuota 21%',
        'O' => 'Base Otros',
        'P' => 'Cuota Otros',
        'Q' => 'Base Total',
        'R' => 'Cuota Total',
        'S' => 'Total',
        'T' => 'Divisa',
        'U' => 'Canal',
        'V' => 'Rectifica a',
        'W' => 'Estado',
    ];

    public function export(iterable $invoices): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Libro registro');

        $this->writeHeaders($sheet);

        $row = 2;
        foreach ($invoices as $invoice) {
            $this->writeRow($sheet, $row, $invoice);
            ++$row;
        }

        $this->applyColumnWidths($sheet);
        $sheet->freezePane('A2');

        return $spreadsheet;
    }

    private function writeHeaders(Worksheet $sheet): void
    {
        foreach (self::HEADERS as $column => $label) {
            $sheet->setCellValue($column . '1', $label);
        }
        $last = array_key_last(self::HEADERS);
        $range = sprintf('A1:%s1', $last);

        $style = $sheet->getStyle($range);
        $style->getFont()->setBold(true);
        $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E0E0E0');
        $style->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $style->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
    }

    private function writeRow(Worksheet $sheet, int $row, InvoiceInterface $invoice): void
    {
        $billing = $invoice->getBillingData();

        $sheet->setCellValueExplicit('A' . $row, $invoice->getNumber(), DataType::TYPE_STRING);
        $sheet->setCellValue('B' . $row, ExcelDate::PHPToExcel($invoice->getIssuedAt()));
        $sheet->setCellValue('C' . $row, $invoice->getType() === InvoiceTypeEnum::RECTIFYING ? 'R' : 'F');
        $sheet->setCellValueExplicit('D' . $row, (string) $billing->getTaxId(), DataType::TYPE_STRING);
        $sheet->setCellValue('E' . $row, $billing->getFullName());
        $sheet->setCellValue('F' . $row, $this->formatAddress($invoice));

        $buckets = $this->bucketize($invoice);
        $sheet->setCellValue('G' . $row, $buckets['0.00']['base'] / 100.0);
        $sheet->setCellValue('H' . $row, $buckets['0.00']['amount'] / 100.0);
        $sheet->setCellValue('I' . $row, $buckets['4.00']['base'] / 100.0);
        $sheet->setCellValue('J' . $row, $buckets['4.00']['amount'] / 100.0);
        $sheet->setCellValue('K' . $row, $buckets['10.00']['base'] / 100.0);
        $sheet->setCellValue('L' . $row, $buckets['10.00']['amount'] / 100.0);
        $sheet->setCellValue('M' . $row, $buckets['21.00']['base'] / 100.0);
        $sheet->setCellValue('N' . $row, $buckets['21.00']['amount'] / 100.0);
        $sheet->setCellValue('O' . $row, $buckets[self::RATE_OTHER]['base'] / 100.0);
        $sheet->setCellValue('P' . $row, $buckets[self::RATE_OTHER]['amount'] / 100.0);

        $sheet->setCellValue('Q' . $row, $invoice->getSubtotal() / 100.0);
        $sheet->setCellValue('R' . $row, $invoice->getTaxesTotal() / 100.0);
        $sheet->setCellValue('S' . $row, $invoice->getTotal() / 100.0);
        $sheet->setCellValue('T' . $row, $invoice->getCurrencyCode());
        $sheet->setCellValue('U' . $row, $invoice->getChannel()->getCode());
        $sheet->setCellValueExplicit(
            'V' . $row,
            $invoice->getRectifiedInvoice()?->getNumber() ?? '',
            DataType::TYPE_STRING,
        );
        $sheet->setCellValue('W' . $row, $invoice->getState());

        $this->applyRowFormatting($sheet, $row);
    }

    /**
     * @return array<string, array{base: int, amount: int}>
     */
    private function bucketize(InvoiceInterface $invoice): array
    {
        $buckets = $this->emptyBuckets();

        foreach ($invoice->getTaxItems() as $taxItem) {
            $rate = number_format((float) $taxItem->getRate(), 2, '.', '');
            $key = in_array($rate, self::RATE_BUCKETS, true) ? $rate : self::RATE_OTHER;

            $buckets[$key]['base'] += $taxItem->getBase();
            $buckets[$key]['amount'] += $taxItem->getAmount();
        }

        return $buckets;
    }

    /** @return array<string, array{base: int, amount: int}> */
    private function emptyBuckets(): array
    {
        $buckets = [];
        foreach (self::RATE_BUCKETS as $rate) {
            $buckets[$rate] = ['base' => 0, 'amount' => 0];
        }
        $buckets[self::RATE_OTHER] = ['base' => 0, 'amount' => 0];

        return $buckets;
    }

    private function formatAddress(InvoiceInterface $invoice): string
    {
        $b = $invoice->getBillingData();
        $parts = array_filter([
            $b->getStreet(),
            $b->getPostcode(),
            $b->getCity(),
            $b->getProvinceName() ?: $b->getProvinceCode(),
            $b->getCountryCode(),
        ], static fn (?string $v): bool => $v !== null && $v !== '');

        return implode(', ', $parts);
    }

    private function applyRowFormatting(Worksheet $sheet, int $row): void
    {
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        foreach (['G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S'] as $column) {
            $sheet->getStyle($column . $row)->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    private function applyColumnWidths(Worksheet $sheet): void
    {
        foreach (array_keys(self::HEADERS) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }
}
