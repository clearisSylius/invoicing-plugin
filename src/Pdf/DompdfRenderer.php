<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Thin Dompdf wrapper. Configures Unicode-friendly defaults (UTF-8 + remote
 * resources off for safety + chroot to project_dir to prevent template-side
 * file inclusions outside the host application).
 *
 * Remote image loading is intentionally DISABLED — if a template needs the
 * shop logo it must be served from the host filesystem (a local path or a
 * base64-encoded data URI in the template). This avoids opening an SSRF
 * surface on every invoice emission.
 */
final class DompdfRenderer implements PdfRendererInterface
{
    public function __construct(
        private readonly string $paperSize = 'A4',
        private readonly string $orientation = 'portrait',
        private readonly string $chroot = '/',
    ) {
    }

    public function renderHtmlToPdf(string $html): string
    {
        $options = new Options();
        $options->setIsHtml5ParserEnabled(true);
        $options->setIsRemoteEnabled(false);
        $options->setChroot($this->chroot);
        $options->setDefaultFont('DejaVu Sans'); // ships with Dompdf, full Unicode coverage.

        $dompdf = new Dompdf($options);
        $dompdf->setPaper($this->paperSize, $this->orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        $output = $dompdf->output();
        if ($output === '') {
            // Defensivo: Dompdf 3.x declara `output(): string` (no `?string`)
            // pero un render fallido puede devolver string vacío. Mejor un
            // 500 explícito que pasar PDF vacío al cliente.
            throw new \RuntimeException('Dompdf returned an empty output.');
        }

        return $output;
    }
}
