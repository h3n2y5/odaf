<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base TestCase untuk Feature test (membutuhkan aplikasi Laravel ter-boot).
 * Dipakai untuk smoke test HTTP terhadap runtime (dijalankan di dalam container
 * dengan koneksi Oracle aktif).
 */
abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
