<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Odaf\Compiler\Contracts\CompilationException;
use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;
use Odaf\Compiler\Contracts\MetadataCompilerInterface;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Odaf\Runtime\RuntimePackageRepository;
use Throwable;

/**
 * Mengompilasi metadata sebuah aplikasi menjadi Runtime Package dan (opsional)
 * mengaktifkannya. Alur inti F1: metadata design-time -> package terkompilasi.
 *
 * Contoh:
 *   php artisan odaf:compile ODAF_DEMO --activate
 *   php artisan odaf:compile ODAF_DEMO --dry-run
 */
final class CompileApplicationCommand extends Command
{
    protected $signature = 'odaf:compile
        {application : OBJECT_CODE atau OBJECT_ID (hex) aplikasi}
        {--activate : Aktifkan package setelah kompilasi}
        {--dry-run : Hanya validasi metadata tanpa menyimpan package}';

    protected $description = 'Kompilasi metadata aplikasi menjadi Runtime Package (BB-02).';

    public function handle(
        MetadataRepositoryInterface $repository,
        MetadataCompilerInterface $compiler,
        RuntimePackageRepository $packages,
    ): int {
        $identifier = (string) $this->argument('application');

        try {
            $applicationId = $this->resolveApplicationId($repository, $identifier);
            if ($applicationId === null) {
                $this->error("Aplikasi tidak ditemukan: {$identifier}");

                return self::FAILURE;
            }

            $graph = $repository->loadApplicationGraph($applicationId);
            $this->info("Memuat graph metadata: {$graph->objectCode()} ({$graph->objectName()})");

            if ($this->option('dry-run')) {
                return $this->runDryRun($compiler, $graph);
            }

            $package = $compiler->compile($graph);

            $this->line('  Package version : '.$package->packageVersion());
            $this->line('  Checksum        : '.$package->checksum());

            $packages->store($package);
            $this->info('Package disimpan ke Runtime Repository (RT_PACKAGE).');

            if ($this->option('activate')) {
                $packages->activate($package->applicationId(), $package->packageVersion());
                $this->info('Package diaktifkan.');
            } else {
                $this->comment('Gunakan --activate untuk mengaktifkan package ini.');
            }

            return self::SUCCESS;
        } catch (CompilationException $e) {
            $this->error($e->getMessage());
            $this->renderDiagnostics($e->diagnostics());

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Kompilasi gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function resolveApplicationId(MetadataRepositoryInterface $repository, string $identifier): ?string
    {
        // Hex 32-char dianggap OBJECT_ID.
        if (preg_match('/^[0-9A-Fa-f]{32}$/', $identifier) === 1) {
            return strtoupper($identifier);
        }

        $app = $repository->findByCode('APP', $identifier);

        return $app?->objectId();
    }

    private function runDryRun(MetadataCompilerInterface $compiler, $graph): int
    {
        $diagnostics = $compiler->validate($graph);
        if ($diagnostics === []) {
            $this->info('Validasi lulus tanpa diagnostik.');

            return self::SUCCESS;
        }

        $this->renderDiagnostics($diagnostics);
        $hasError = array_filter(
            $diagnostics,
            static fn (CompilerDiagnosticInterface $d): bool => $d->severity() === CompilerDiagnosticInterface::SEVERITY_ERROR,
        );

        return $hasError === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<int, CompilerDiagnosticInterface>  $diagnostics
     */
    private function renderDiagnostics(array $diagnostics): void
    {
        if ($diagnostics === []) {
            return;
        }

        $this->table(
            ['Severity', 'Code', 'Message', 'Object'],
            array_map(static fn (CompilerDiagnosticInterface $d): array => [
                $d->severity(),
                $d->code(),
                $d->message(),
                $d->objectId() ?? '-',
            ], $diagnostics),
        );
    }
}
