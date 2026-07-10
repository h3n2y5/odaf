<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Facades\DB;

/**
 * Menetapkan (reset) password seorang pengguna SEC_USER.
 *
 * Berguna untuk instalasi lama yang masih memakai PASSWORD_HASH placeholder,
 * atau untuk membuat kredensial pengguna baru.
 *
 * Contoh:
 *   php artisan odaf:user:password admin
 *   php artisan odaf:user:password admin --password=rahasia
 */
final class SetUserPasswordCommand extends Command
{
    protected $signature = 'odaf:user:password
        {username : OBJECT_CODE (username) pada SEC_USER}
        {--password= : Password baru (bila kosong akan diminta secara interaktif)}';

    protected $description = 'Set/reset password pengguna SEC_USER (bcrypt).';

    public function handle(Hasher $hasher): int
    {
        $username = (string) $this->argument('username');
        $connection = DB::connection((string) config('database.default', 'oracle'));

        $user = $connection->selectOne(
            'SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, OBJECT_NAME FROM SEC_USER WHERE OBJECT_CODE = ?',
            [$username],
        );

        if ($user === null) {
            $this->error("Pengguna tidak ditemukan: {$username}");

            return self::FAILURE;
        }

        $password = (string) ($this->option('password') ?? '');
        if ($password === '') {
            $password = (string) $this->secret('Password baru');
            $confirm = (string) $this->secret('Ulangi password');
            if ($password !== $confirm) {
                $this->error('Password tidak cocok.');

                return self::FAILURE;
            }
        }

        if ($password === '') {
            $this->error('Password tidak boleh kosong.');

            return self::FAILURE;
        }

        $affected = $connection->update(
            'UPDATE SEC_USER SET PASSWORD_HASH = ?, UPDATED_AT = SYSTIMESTAMP WHERE OBJECT_CODE = ?',
            [$hasher->make($password), $username],
        );

        if ($affected === 0) {
            $this->error('Gagal memperbarui password.');

            return self::FAILURE;
        }

        $this->info("Password untuk '{$username}' berhasil diperbarui.");

        return self::SUCCESS;
    }
}
