<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Storage;

interface PdfStorageInterface
{
    /**
     * Persist a PDF blob and return a path the rest of the plugin can use
     * to retrieve it later. Implementations decide the on-disk layout; the
     * returned path is opaque to callers.
     */
    public function store(string $relativePath, string $contents): string;

    /**
     * Read a previously stored PDF. Returns the raw bytes.
     *
     * @throws \RuntimeException when the file is missing.
     */
    public function read(string $path): string;

    /**
     * Whether a PDF exists at the given path.
     */
    public function exists(string $path): bool;
}
