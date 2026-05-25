<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\EventListener;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Event\InvoiceIssuedEvent;
use ClearisSylius\InvoicingPlugin\Event\InvoiceRectifiedEvent;
use ClearisSylius\InvoicingPlugin\Mailer\InvoiceMailerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Triggers customer emails on emission and on rectification, subject to the
 * per-channel `sendEmailOnIssue` flag.
 *
 * Resolución del sender (orden de prioridad):
 *   1. `ChannelInvoicingSettings.senderEmail/Name` del canal de la factura.
 *   2. Fallback global del plugin: parameter `sender_address` configurable
 *      vía `clearis_sylius_invoicing.email.sender_address` en el YAML del
 *      host (que a su vez puede leer un env var).
 *   3. Si ninguno, se loggea un info y se salta el envío. La factura ya
 *      está emitida — el email es accesorio, no rompemos la emisión.
 */
final class SendInvoiceEmailListener
{
    public function __construct(
        private readonly InvoiceMailerInterface $mailer,
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly ?string $defaultSenderAddress = null,
        private readonly ?string $defaultSenderName = null,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
    }

    #[AsEventListener]
    public function onIssued(InvoiceIssuedEvent $event): void
    {
        $settings = $this->settingsRepository->findByChannel($event->invoice->getChannel());
        if ($settings === null || !$settings->isSendEmailOnIssue()) {
            return;
        }

        [$address, $name] = $this->resolveSender($settings);
        if ($address === null) {
            $this->logger->info('Skipping invoice email: no sender configured.', [
                'invoice' => $event->invoice->getNumber(),
                'channel' => $event->invoice->getChannel()->getCode(),
            ]);

            return;
        }

        $this->mailer->sendInvoiceIssued($event->invoice, $address, $name);
    }

    #[AsEventListener]
    public function onRectified(InvoiceRectifiedEvent $event): void
    {
        $settings = $this->settingsRepository->findByChannel($event->rectifyingInvoice->getChannel());
        if ($settings === null || !$settings->isSendEmailOnIssue()) {
            return;
        }

        [$address, $name] = $this->resolveSender($settings);
        if ($address === null) {
            $this->logger->info('Skipping rectifying invoice email: no sender configured.', [
                'invoice' => $event->rectifyingInvoice->getNumber(),
                'channel' => $event->rectifyingInvoice->getChannel()->getCode(),
            ]);

            return;
        }

        $this->mailer->sendRectifyingInvoiceIssued(
            $event->rectifyingInvoice,
            $event->originalInvoice,
            $address,
            $name,
        );
    }

    /**
     * @return array{0: ?string, 1: ?string}
     */
    private function resolveSender(\ClearisSylius\InvoicingPlugin\Model\ChannelInvoicingSettingsInterface $settings): array
    {
        $address = $settings->getSenderEmail() ?: $this->defaultSenderAddress;
        $name = $settings->getSenderName() ?: $this->defaultSenderName;

        return [$address ?: null, $name ?: null];
    }
}
