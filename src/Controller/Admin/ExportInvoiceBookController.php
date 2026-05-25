<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Exporter\InvoiceBookExporterInterface;
use ClearisSylius\InvoicingPlugin\Form\Type\ExportInvoiceBookRangeType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;

/**
 * Two-mode controller:
 *
 *  - GET without `submit=1` renders the date-range / channel form so the
 *    admin picks the export window.
 *  - GET with `submit=1` and the form params streams the .xlsx download
 *    directly (the form is submitted via GET so the URL is bookmarkable).
 */
final class ExportInvoiceBookController
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceBookExporterInterface $exporter,
        private readonly FormFactoryInterface $formFactory,
        private readonly Environment $twig,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->formFactory->create(ExportInvoiceBookRangeType::class);
        $form->handleRequest($request);

        if (!$form->isSubmitted() || !$form->isValid()) {
            return new Response($this->twig->render(
                '@ClearisSyliusInvoicingPlugin/admin/invoice/export_book.html.twig',
                ['form' => $form->createView()],
            ));
        }

        /** @var array{from: ?\DateTimeImmutable, to: ?\DateTimeImmutable, channel: ?\Sylius\Component\Channel\Model\ChannelInterface} $data */
        $data = $form->getData();

        $invoices = $this->invoiceRepository->streamForBookExport(
            $data['channel'] ?? null,
            $data['from'] ?? null,
            isset($data['to']) ? $data['to']->setTime(23, 59, 59) : null,
        );

        $spreadsheet = $this->exporter->export($invoices);
        $writer = new XlsxWriter($spreadsheet);

        $filename = sprintf('libro_registro_%s.xlsx', (new \DateTimeImmutable())->format('Ymd_His'));

        $response = new StreamedResponse(static function () use ($writer): void {
            $writer->save('php://output');
        });
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', sprintf('attachment; filename="%s"', $filename));
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }
}
