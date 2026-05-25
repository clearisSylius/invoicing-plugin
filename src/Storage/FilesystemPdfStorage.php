<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Storage;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Local-disk PDF storage. The base directory comes from
 * `clearis_sylius_invoicing.pdf.storage_directory`. Paths returned by store()
 * are RELATIVE to that directory so the invoice row remains portable across
 * environments (CI, prod) — at read time we resolve them against the
 * configured base.
 */
final class FilesystemPdfStorage implements PdfStorageInterface
{
    public function __construct(
        private readonly string $baseDirectory,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
    }

    public function store(string $relativePath, string $contents): string
    {
        $absolute = $this->resolve($relativePath);
        $this->filesystem->mkdir(dirname($absolute));
        $this->filesystem->dumpFile($absolute, $contents);

        return $relativePath;
    }

    public function read(string $path): string
    {
        $absolute = $this->resolve($path);
        if (!is_file($absolute)) {
            throw new \RuntimeException(sprintf('Invoice PDF not found at "%s".', $absolute));
        }

        $contents = file_get_contents($absolute);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Failed to read PDF at "%s".', $absolute));
        }

        return $contents;
    }

    public function exists(string $path): bool
    {
        return is_file($this->resolve($path));
    }

    private function resolve(string $relativePath): string
    {
        $relative = ltrim($relativePath, '/');

        return rtrim($this->baseDirectory, '/') . '/' . $relative;
    }
}
