<?php

declare(strict_types=1);

namespace ClearisSylius\InvoicingPlugin\Migration\Command;

use ClearisSylius\InvoicingPlugin\Migration\ImportReportEntry;
use ClearisSylius\InvoicingPlugin\Migration\LegacyDetector;
use ClearisSylius\InvoicingPlugin\Migration\LegacyInvoiceImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'clearis:invoicing:import-from-official',
    description: 'Imports invoices from sylius/invoicing-plugin into clearissylius/invoicing-plugin.',
)]
final class ImportFromOfficialCommand extends Command
{
    public function __construct(
        private readonly LegacyDetector $detector,
        private readonly LegacyInvoiceImporter $importer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Read and report, but do not persist anything.')
            ->addOption('from-date', null, InputOption::VALUE_REQUIRED, 'Only import invoices issued from this date (YYYY-MM-DD).')
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Limit to a single channel by code.')
            ->addOption('batch', null, InputOption::VALUE_REQUIRED, 'Flush + clear EntityManager every N imports.', '500')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = $this->detector->detect();
        $io->title('clearis:invoicing:import-from-official');

        if ($path === LegacyDetector::PATH_NONE) {
            $io->success('No legacy data found. Nothing to import.');

            return self::SUCCESS;
        }

        $io->writeln(sprintf('Detected legacy path: <info>%s</info>', $path));

        $dryRun = (bool) $input->getOption('dry-run');
        if ($dryRun) {
            $io->warning('Dry-run mode — no changes will be persisted.');
        }

        $fromDate = $input->getOption('from-date') !== null
            ? new \DateTimeImmutable((string) $input->getOption('from-date'))
            : null;
        $channelCode = $input->getOption('channel') !== null ? (string) $input->getOption('channel') : null;
        $batchSize = max(1, (int) $input->getOption('batch'));

        $stats = ['imported' => 0, 'skipped' => 0, 'warnings' => 0];

        foreach ($this->importer->import($fromDate, $channelCode, $batchSize, $dryRun) as $entry) {
            /** @var ImportReportEntry $entry */
            $key = $this->statsKey($entry->status);
            $stats[$key] = ($stats[$key] ?? 0) + 1;

            if ($output->isVerbose() || $entry->status !== ImportReportEntry::STATUS_IMPORTED) {
                $io->writeln(sprintf(
                    ' · #%s %s [<%s>%s</>] %s',
                    $entry->legacyId,
                    str_pad($entry->number, 16),
                    $this->statusColor($entry->status),
                    $entry->status,
                    $entry->message ?? '',
                ));
            }
        }

        $io->newLine();
        $io->table(['Imported', 'Skipped', 'Warnings'], [[
            $stats['imported'] ?? 0,
            $stats['skipped'] ?? 0,
            $stats['warnings'] ?? 0,
        ]]);

        $io->success('Done.');

        return self::SUCCESS;
    }

    private function statsKey(string $status): string
    {
        return match ($status) {
            ImportReportEntry::STATUS_IMPORTED => 'imported',
            ImportReportEntry::STATUS_SKIPPED => 'skipped',
            ImportReportEntry::STATUS_WARNING => 'warnings',
            default => 'other',
        };
    }

    private function statusColor(string $status): string
    {
        return match ($status) {
            ImportReportEntry::STATUS_IMPORTED => 'fg=green',
            ImportReportEntry::STATUS_SKIPPED => 'fg=yellow',
            ImportReportEntry::STATUS_WARNING => 'fg=red',
            default => 'comment',
        };
    }
}
