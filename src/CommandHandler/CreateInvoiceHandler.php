<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\CommandHandler;

use ClearisSylius\InvoicingPlugin\Command\CreateInvoice;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceRepositoryInterface;
use ClearisSylius\InvoicingPlugin\Event\InvoiceIssuedEvent;
use ClearisSylius\InvoicingPlugin\Factory\InvoiceFactoryInterface;
use ClearisSylius\InvoicingPlugin\Pdf\InvoicePdfGeneratorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Idempotent handler: if an invoice already exists for the order, do nothing
 * (returns the existing). This is important because event listeners can fire
 * the command twice (e.g. payment state machine re-applied) and we never want
 * duplicate emissions per order at the standard tier.
 */
#[AsMessageHandler]
final class CreateInvoiceHandler
{
    /**
     * @param OrderRepositoryInterface<OrderInterface> $orderRepository
     */
    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly InvoiceRepositoryInterface $invoiceRepository,
        private readonly InvoiceFactoryInterface $invoiceFactory,
        private readonly InvoicePdfGeneratorInterface $pdfGenerator,
        private readonly EntityManagerInterface $entityManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function __invoke(CreateInvoice $command): void
    {
        $order = $this->orderRepository->findOneByNumber($command->orderNumber);
        if (!$order instanceof OrderInterface) {
            $this->logger->warning('Cannot create invoice: order {orderNumber} not found.', [
                'orderNumber' => $command->orderNumber,
            ]);

            return;
        }

        // Already-emitted standard invoice → noop.
        foreach ($this->invoiceRepository->findByOrder($order) as $existing) {
            if ($existing->getType() === \ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum::STANDARD) {
                return;
            }
        }

        $invoice = $this->invoiceFactory->createFromOrder($order);
        $this->entityManager->persist($invoice);
        $this->entityManager->flush();

        // Render and persist the PDF. PDF generation failure must NOT prevent
        // the invoice from being committed — the invoice is the legal record,
        // the PDF is a derived artifact. Log and continue.
        try {
            $pdfPath = $this->pdfGenerator->generate($invoice);
            $invoice->setPdfPath($pdfPath);
            $this->entityManager->flush();
        } catch (\Throwable $e) {
            $this->logger->error('Failed to render PDF for invoice {number}: {error}', [
                'number' => $invoice->getNumber(),
                'error' => $e->getMessage(),
            ]);
        }

        $this->eventDispatcher->dispatch(new InvoiceIssuedEvent($invoice));
    }
}
