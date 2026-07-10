<?php

declare(strict_types=1);
use PHPUnit\Framework\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
| Unit test ODAF bersifat framework-free (tanpa boot Laravel/Oracle) sehingga
| terikat langsung ke PHPUnit\Framework\TestCase. Ini memungkinkan CI menjalankan
| pengujian logika compiler/validation/identity tanpa database.
*/

uses(TestCase::class)->in('Unit');

// Feature test membutuhkan aplikasi Laravel ter-boot (Oracle aktif di container).
uses(Tests\TestCase::class)->in('Feature');
