<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Mailer;

use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Storage\PdfStorageInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Twig\Environment;

/**
 * Envía la factura como email con el PDF adjunto.
 *
 * El sender (from + name) se RECIBE como parámetro en cada llamada — no se
 * inyecta como dependencia — porque la resolución es per-canal: el listener
 * mira `ChannelInvoicingSettings.senderEmail/Name` y cae al fallback global
 * sólo si están vacíos. Centralizar la resolución en el listener evita que
 * el mailer tenga que conocer el `ChannelInvoicingSettingsRepository`.
 *
 * Si el sender llega null/vacío (ni canal ni global configurado), el método
 * NO envía el email pero TAMPOCO lanza — la emisión de la factura es lo
 * fundamental, el envío es accesorio. El listener loggea el skip.
 */
final class InvoiceMailer implements InvoiceMailerInterface
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Environment $twig,
        private readonly PdfStorageInterface $pdfStorage,
    ) {
    }

    public function sendInvoiceIssued(
        InvoiceInterface $invoice,
        ?string $senderAddress,
        ?string $senderName = null,
    ): void {
        $this->send(
            $invoice,
            '@ClearisSyliusInvoicingPlugin/email/invoice_issued.html.twig',
            'clearis.email.invoice_issued.subject',
            $senderAddress,
            $senderName,
        );
    }

    public function sendRectifyingInvoiceIssued(
        InvoiceInterface $rectifying,
        InvoiceInterface $original,
        ?string $senderAddress,
        ?string $senderName = null,
    ): void {
        $this->send(
            $rectifying,
            '@ClearisSyliusInvoicingPlugin/email/invoice_rectifying_issued.html.twig',
            'clearis.email.invoice_rectifying.subject',
            $senderAddress,
            $senderName,
            ['original' => $original],
        );
    }

    /**
     * @param array<string, mixed> $extraContext
     */
    private function send(
        InvoiceInterface $invoice,
        string $template,
        string $subjectKey,
        ?string $senderAddress,
        ?string $senderName,
        array $extraContext = [],
    ): void {
        // Skip silenciosamente si no hay sender configurado. El listener ya
        // se encargó de loggear el motivo.
        if ($senderAddress === null || $senderAddress === '') {
            return;
        }

        $customerEmail = $invoice->getOrder()->getCustomer()?->getEmail();
        if ($customerEmail === null) {
            return;
        }

        $html = $this->twig->render($template, [
            'invoice' => $invoice,
        ] + $extraContext);

        $fromName = $senderName ?? 'Facturación';
        $email = (new Email())
            ->from(sprintf('%s <%s>', $fromName, $senderAddress))
            ->to($customerEmail)
            ->subject($this->translateSubject($subjectKey, $invoice))
            ->html($html);

        $pdfPath = $invoice->getPdfPath();
        if ($pdfPath !== null && $this->pdfStorage->exists($pdfPath)) {
            $email->attach(
                $this->pdfStorage->read($pdfPath),
                sprintf('factura_%s.pdf', preg_replace('~[^A-Za-z0-9_\-]+~', '_', $invoice->getNumber())),
                'application/pdf',
            );
        }

        $this->mailer->send($email);
    }

    private function translateSubject(string $key, InvoiceInterface $invoice): string
    {
        // The subject is rendered with simple {number} interpolation rather
        // than full i18n to avoid pulling the Translator into the mailer.
        // Hosts that need full localisation can override this service.
        return str_replace('{number}', $invoice->getNumber(), match ($key) {
            'clearis.email.invoice_issued.subject' => sprintf('Factura %s', '{number}'),
            'clearis.email.invoice_rectifying.subject' => sprintf('Factura rectificativa %s', '{number}'),
            default => $key,
        });
    }
}
