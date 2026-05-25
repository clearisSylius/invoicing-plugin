<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\CommandHandler;

use ClearisSylius\InvoicingPlugin\Command\RegeneratePdf;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Pdf\InvoicePdfGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RegeneratePdfHandler
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoicePdfGeneratorInterface $pdfGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(RegeneratePdf $command): void
    {
        $invoice = $this->invoiceRepository->find($command->invoiceId);
        if ($invoice === null) {
            throw new \RuntimeException(sprintf('Invoice #%d not found.', $command->invoiceId));
        }

        $pdfPath = $this->pdfGenerator->generate($invoice);
        $invoice->setPdfPath($pdfPath);
        $this->entityManager->flush();
    }
}
