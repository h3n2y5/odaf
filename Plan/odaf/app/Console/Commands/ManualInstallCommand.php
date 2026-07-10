<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\ManualRepository;
use Illuminate\Console\Command;
use Throwable;

/**
 * Install (buat tabel APP_MANUAL) + seed konten manual default bila kosong.
 *
 * Contoh:
 *   php artisan odaf:manual-install
 */
final class ManualInstallCommand extends Command
{
    protected $signature = 'odaf:manual-install';

    protected $description = 'Buat tabel APP_MANUAL dan seed konten manual default (idempotent).';

    public function handle(ManualRepository $repo): int
    {
        try {
            $repo->ensureInstalled();
        } catch (Throwable $e) {
            $this->error('Gagal meng-install manual: '.$e->getMessage());

            return self::FAILURE;
        }

        $sections = $repo->sections();
        $this->info('Manual siap. Jumlah bagian: '.count($sections));

        $this->table(
            ['#', 'Judul', 'Slug'],
            array_map(
                static fn (int $i, array $s): array => [$i + 1, $s['TITLE'], $s['SLUG']],
                array_keys($sections),
                $sections,
            ),
        );

        $this->comment('Buka halaman manual di: '.url('/manual'));

        return self::SUCCESS;
    }
}
