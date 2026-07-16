<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$repo = app(\App\Support\ManualRepository::class);
$title = 'Sinkronisasi (Ekspor/Impor) Aplikasi';
$body = <<< 'MD'
ODAF memiliki fitur bawaan untuk melakukan penarikan (ekspor) keseluruhan arsitektur aplikasi (metadata) beserta tabel fisik dan isi datanya secara penuh, untuk kemudian dipindahkan (impor) ke server lain.

Proses ini dilakukan melalui antarmuka baris perintah (CLI) atau terminal server. 

### 1. Ekspor (Menyedot Aplikasi)
Jalankan perintah ini di server sumber (lokal):
```bash
docker compose exec app php artisan odaf:metadata-export <KODE_APLIKASI>
# Contoh: docker compose exec app php artisan odaf:metadata-export NEXUS
```
Perintah ini akan secara ajaib menyedot:
1. Seluruh susunan menu, form, kolom, dan akses kontrol (Metadata).
2. Seluruh tabel fisik di Oracle yang berawalan `<KODE_APLIKASI>_` (DDL).
3. Seluruh isi baris data dari tabel-tabel fisik tersebut (DML).

Hasilnya akan disimpan dalam **satu file JSON** di `storage/app/metadata/<KODE_APLIKASI>.json`.

### 2. Impor (Menimpa ke Server Tujuan)
Pastikan Anda telah menyalin file JSON hasil ekspor tadi ke server tujuan di dalam direktori `storage/app/metadata/`.
Jalankan perintah ini di server tujuan:
```bash
docker compose exec app php artisan odaf:metadata-import storage/app/metadata/<KODE_APLIKASI>.json
# Contoh: docker compose exec app php artisan odaf:metadata-import storage/app/metadata/NEXUS.json
```
Perintah ini akan:
1. Memasukkan seluruh metadata form dan menu.
2. Membentuk tabel-tabel fisik jika belum ada.
3. **Mengosongkan** tabel fisik lama, lalu memasukkan data hasil ekspor (konsep timpa semua / overwrite).
4. Melakukan *Compile & Activate* secara otomatis agar aplikasi langsung dapat dipakai.
MD;

// Check if already exists
$existing = \Illuminate\Support\Facades\DB::table('APP_MANUAL')->where('TITLE', $title)->first();
if (!$existing) {
    $repo->create($title, $repo->uniqueSlug($title), $body, 90, 'SYSTEM');
    echo "Inserted manual successfully.\n";
} else {
    echo "Manual already exists.\n";
}
