<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Odaf\Studio\MetadataScaffolder;
use Throwable;

/**
 * Generate metadata ODAF (dataset + form + grid + menu + validasi) dari sebuah
 * tabel Oracle yang sudah ada — "form generator" bergaya framework.
 *
 * Contoh:
 *   php artisan odaf:scaffold PRODUCT --app=ODAF_DEMO --label="Produk" --compile
 *   php artisan odaf:scaffold PRODUCT --force --compile
 */
final class ScaffoldTableCommand extends Command
{
    protected $signature = 'odaf:scaffold
        {table : Nama tabel Oracle yang sudah ada (mis. PRODUCT)}
        {--app=ODAF_DEMO : OBJECT_CODE aplikasi tujuan}
        {--module=MASTER : OBJECT_CODE modul (dibuat bila belum ada)}
        {--module-name= : Nama modul bila dibuat baru}
        {--label= : Judul halaman/menu (default: dari nama tabel)}
        {--icon= : Ikon menu (opsional)}
        {--force : Timpa metadata bila tabel sudah pernah di-scaffold}
        {--compile : Kompilasi & aktifkan package setelah generate}';

    protected $description = 'Generate metadata CRUD (form/grid/menu) dari tabel Oracle yang sudah ada (ODAF Studio).';

    public function handle(MetadataScaffolder $scaffolder): int
    {
        $table = strtoupper((string) $this->argument('table'));
        $appCode = (string) $this->option('app');

        try {
            $result = $scaffolder->scaffold([
                'table' => $table,
                'appCode' => $appCode,
                'moduleCode' => strtoupper((string) $this->option('module')),
                'moduleName' => (string) ($this->option('module-name') ?: ''),
                'label' => (string) ($this->option('label') ?: ''),
                'menuIcon' => $this->option('icon') ?: null,
                'force' => (bool) $this->option('force'),
            ]);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Metadata untuk tabel {$result['table']} berhasil dibuat.");
        $this->table(
            ['Objek', 'Kode / Nilai'],
            [
                ['Dataset', $result['datasetCode']],
                ['Halaman (form/grid)', $result['pageCode']],
                ['Menu', $result['menuCode'].' (modul '.$result['moduleCode'].')'],
                ['Primary key', $result['primaryKey']],
                ['Soft delete', $result['softDelete'] ? 'ya' : 'tidak'],
                ['Jumlah field', (string) $result['fields']],
                ['Jumlah aturan validasi', (string) $result['rules']],
            ],
        );

        if ($this->option('compile')) {
            $this->line('');
            $this->info('Mengompilasi & mengaktifkan package...');
            $exit = $this->call('odaf:compile', ['application' => $appCode, '--activate' => true]);
            if ($exit !== self::SUCCESS) {
                return self::FAILURE;
            }
            $this->line('');
            $this->info('Selesai. Buka: '.url('/app/'.$appCode.'/g/'.$result['pageCode']));
        } else {
            $this->comment('Jalankan kompilasi untuk mengaktifkan: php artisan odaf:compile '.$appCode.' --activate');
        }

        return self::SUCCESS;
    }
}
