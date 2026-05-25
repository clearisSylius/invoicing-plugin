<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Pdf;

interface PdfRendererInterface
{
    /**
     * Render an HTML string to a binary PDF blob.
     *
     * Implementations decide paper size and orientation from configuration.
     */
    public function renderHtmlToPdf(string $html): string;
}
