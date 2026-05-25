<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\CommandHandler;

use ClearisSylius\InvoicingPlugin\Command\RectifyInvoice;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Event\InvoiceCancelledEvent;
use ClearisSylius\InvoicingPlugin\Event\InvoiceRectifiedEvent;
use ClearisSylius\InvoicingPlugin\Factory\RectifyingInvoiceFactoryInterface;
use ClearisSylius\InvoicingPlugin\Pdf\InvoicePdfGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RectifyInvoiceHandler
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly RectifyingInvoiceFactoryInterface $factory,
        private readonly InvoicePdfGeneratorInterface $pdfGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(RectifyInvoice $command): void
    {
        $original = $this->invoiceRepository->find($command->originalInvoiceId);
        if ($original === null) {
            throw new \RuntimeException(sprintf('Invoice #%d not found.', $command->originalInvoiceId));
        }

        $rectifying = $this->factory->create(
            $original,
            $command->reason,
            $command->isTotal,
            [
                'base' => $command->baseDelta,
                'taxes' => $command->taxesDelta,
                'total' => $command->totalDelta !== 0 ? $command->totalDelta : $command->baseDelta + $command->taxesDelta,
            ],
        );

        $this->entityManager->persist($rectifying);
        $this->entityManager->flush();

        try {
            $pdfPath = $this->pdfGenerator->generate($rectifying);
            $rectifying->setPdfPath($pdfPath);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to render PDF for rectifying invoice {number}: {error}', [
                'number' => $rectifying->getNumber(),
                'error' => $e->getMessage(),
            ]);
        }

        $this->eventDispatcher->dispatch(new InvoiceRectifiedEvent($rectifying, $original, $command->isTotal));
        if ($command->isTotal) {
            $this->eventDispatcher->dispatch(new InvoiceCancelledEvent($original, $rectifying));
        }
    }
}
