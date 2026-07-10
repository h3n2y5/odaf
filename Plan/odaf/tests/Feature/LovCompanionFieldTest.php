<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Test LOV companion field auto-fill saat dropdown berubah.
 * Memastikan field deskripsi (mis. CUSTGROUPDESC) otomatis terisi
 * saat memilih dari dropdown LOV (mis. CUSTGROUP).
 */
final class LovCompanionFieldTest extends TestCase
{
    public function test_companion_field_syncs_when_lov_selected(): void
    {
        $this->markTestIncomplete('Feature test untuk LOV companion field sync — perlu setup data Customer + LOV lengkap.');

        // Setup: buat customer dengan LOV Customer Group yang punya companion field
        // Test: 
        // 1. Buka form edit customer
        // 2. Pilih value dari dropdown CUSTGROUP
        // 3. Verify CUSTGROUPDESC otomatis terisi dengan label yang sesuai
        // 4. Submit form
        // 5. Verify kedua kolom tersimpan dengan benar
    }

    public function test_companion_field_cleared_when_lov_cleared(): void
    {
        $this->markTestIncomplete('Feature test untuk LOV companion field clear — perlu setup data Customer + LOV lengkap.');

        // Setup: buat customer dengan CUSTGROUP & CUSTGROUPDESC terisi
        // Test:
        // 1. Buka form edit customer
        // 2. Clear dropdown CUSTGROUP (pilih "-- pilih --")
        // 3. Verify CUSTGROUPDESC juga ikut kosong
        // 4. Submit form
        // 5. Verify kedua kolom NULL
    }
}
