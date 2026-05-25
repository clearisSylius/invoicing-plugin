<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Pdf;

use ClearisSylius\InvoicingPlugin\Doctrine\ORM\ChannelInvoicingSettingsRepository;
use ClearisSylius\InvoicingPlugin\Doctrine\ORM\InvoiceTemplateRepository;
use ClearisSylius\InvoicingPlugin\Model\InvoiceInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTemplateInterface;
use ClearisSylius\InvoicingPlugin\Model\InvoiceTypeEnum;
use ClearisSylius\InvoicingPlugin\Storage\PdfStorageInterface;
use Twig\Environment;

/**
 * High-level PDF generator. Resolves the right template (channel-specific or
 * the global default), renders the Twig view, hands it off to the PDF
 * renderer, and writes the result to storage at a deterministic path.
 *
 * Path layout: invoices/{YYYY}/{series-code}/{number-slug}.pdf so the
 * filesystem mirrors the libro registro logical structure and rectifying
 * invoices land next to their series too.
 */
final class InvoicePdfGenerator implements InvoicePdfGeneratorInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly PdfRendererInterface $renderer,
        private readonly PdfStorageInterface $storage,
        private readonly InvoiceTemplateRepository $templateRepository,
        private readonly ChannelInvoicingSettingsRepository $settingsRepository,
        private readonly string $defaultTemplateCode = 'default',
    ) {
    }

    public function generate(InvoiceInterface $invoice): string
    {
        $template = $this->resolveTemplate($invoice);

        $html = $this->twig->render($template->getTwigTemplate(), [
            'invoice' => $invoice,
            'template' => $template,
        ]);

        $pdf = $this->renderer->renderHtmlToPdf($html);

        $relativePath = $this->buildPath($invoice);

        return $this->storage->store($relativePath, $pdf);
    }

    private function resolveTemplate(InvoiceInterface $invoice): InvoiceTemplateInterface
    {
        $settings = $this->settingsRepository->findByChannel($invoice->getChannel());

        $template = $invoice->getType() === InvoiceTypeEnum::RECTIFYING
            ? $settings?->getRectifyingTemplate()
            : $settings?->getStandardTemplate();

        if ($template === null) {
            $template = $this->templateRepository->findOneByCode($this->defaultTemplateCode);
        }

        if ($template === null) {
            // Last-resort fallback: synthesise an in-memory template pointing
            // at the bundled Twig view. This keeps the system functional even
            // before any template row has been inserted in admin.
            $tpl = new \ClearisSylius\InvoicingPlugin\Entity\InvoiceTemplate();
            $tpl->setCode($this->defaultTemplateCode);
            $tpl->setTwigTemplate(
                $invoice->getType() === InvoiceTypeEnum::RECTIFYING
                    ? '@ClearisSyliusInvoicingPlugin/pdf/rectifying.html.twig'
                    : '@ClearisSyliusInvoicingPlugin/pdf/default.html.twig',
            );
            $tpl->setType($invoice->getType());

            return $tpl;
        }

        return $template;
    }

    private function buildPath(InvoiceInterface $invoice): string
    {
        $year = $invoice->getIssuedAt()->format('Y');
        $series = $invoice->getSeries()?->getCode() ?? 'unassigned';
        $numberSlug = preg_replace('~[^A-Za-z0-9_\-]+~', '_', $invoice->getNumber()) ?? 'invoice';

        return sprintf('invoices/%s/%s/%s.pdf', $year, $series, $numberSlug);
    }
}
