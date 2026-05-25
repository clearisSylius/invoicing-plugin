<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Controller\Admin;

use ClearisSylius\InvoicingPlugin\Command\RegeneratePdf;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Storage\PdfStorageInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Stream the stored PDF for an invoice. If no PDF exists yet (e.g. an
 * invoice imported from the legacy plugin without a corresponding file),
 * the controller regenerates it on-the-fly so the admin always gets a
 * downloadable artifact.
 */
final class DownloadInvoicePdfController
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $repository,
        private readonly PdfStorageInterface $storage,
        private readonly MessageBusInterface $commandBus,
    ) {
    }

    public function __invoke(int $id): Response
    {
        $invoice = $this->repository->find($id);
        if ($invoice === null) {
            throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
        }

        $path = $invoice->getPdfPath();
        if ($path === null || !$this->storage->exists($path)) {
            // Lazy regeneration: dispatch RegeneratePdf and re-read.
            $this->commandBus->dispatch(new RegeneratePdf((int) $invoice->getId()));
            $path = $invoice->getPdfPath();
        }

        if ($path === null) {
            throw new \RuntimeException(sprintf('Invoice %s has no PDF available.', $invoice->getNumber()));
        }

        $contents = $this->storage->read($path);
        $filename = sprintf('factura_%s.pdf', preg_replace('~[^A-Za-z0-9_\-]+~', '_', $invoice->getNumber()));

        $response = new Response($contents);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s"', $filename));

        return $response;
    }
}
