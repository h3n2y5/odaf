<?php

declare(strict_types=1);

use App\Auth\OdafUser;
use App\Livewire\Runtime\DatasetForm;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Odaf\Studio\ApplicationCompiler;
use Odaf\Studio\TableDataManager;

/**
 * LOV dependen (parametrik) + pengisian kolom label pendamping.
 *
 * Query {@code @NEXUS} tidak tersedia lokal, jadi LOV_CUST_GROUP sementara
 * dialihkan ke query lokal (tabel CUSTOMER) untuk membuktikan mekanisme:
 * substitusi {{CUSTOMER_CODE}} + pengisian CUSTGROUPDESC dari label terpilih.
 * Definisi asli dipulihkan pada blok finally.
 */
function lovDepAdmin(): OdafUser
{
    return new OdafUser(
        objectId: '00000000000000000000000000000001',
        username: 'admin',
        name: 'System Administrator',
        email: null,
        passwordHash: null,
        roleIds: ['00000000000000000000000000000002'],
    );
}

it('LOV parametrik mengisi dropdown & kolom label pendamping (end-to-end)', function (): void {
    $manager = app(TableDataManager::class);
    $compiler = app(ApplicationCompiler::class);
    $conn = DB::connection((string) config('database.default'));
    $appId = '00000000000000000000000000000010';
    $adminId = '00000000000000000000000000000001';

    // Simpan definisi LOV asli (untuk dipulihkan).
    $orig = array_change_key_case((array) $conn->selectOne(
        "SELECT VALUE_COLUMN, LABEL_COLUMN, SOURCE_QUERY FROM DS_LOV WHERE OBJECT_CODE = 'LOV_CUST_GROUP'"
    ), CASE_UPPER);
    $origSrc = is_resource($orig['SOURCE_QUERY']) ? stream_get_contents($orig['SOURCE_QUERY']) : $orig['SOURCE_QUERY'];

    $code = 'LOVX'.substr((string) hrtime(true), -6);
    $custKey = $manager->create('CUSTOMER', [
        'CUSTOMER_CODE' => $code,
        'CUSTOMER_NAME' => 'Lov Cust One',
    ], userId: $adminId);

    try {
        // Alihkan LOV ke query lokal parametrik; label dibuat berbeda ('GRP-...').
        $conn->update(
            "UPDATE DS_LOV SET VALUE_COLUMN = ?, LABEL_COLUMN = ?, SOURCE_QUERY = ? WHERE OBJECT_CODE = 'LOV_CUST_GROUP'",
            ['V', 'L', "SELECT CUSTOMER_CODE AS V, 'GRP-'||CUSTOMER_NAME AS L FROM CUSTOMER WHERE CUSTOMER_CODE = {{CUSTOMER_CODE}}"],
        );
        $compiler->compileOne($appId, $adminId);

        $this->actingAs(lovDepAdmin());

        $component = Livewire::test(DatasetForm::class, [
            'appCode' => 'ODAF_DEMO',
            'pageCode' => 'PAGE_CUSTOMER',
            'key' => $custKey['CUSTOMER_ID'],
        ]);

        // Dropdown terisi opsi hasil query parametrik (CUSTOMER_CODE = record aktif).
        $component->assertSee('GRP-Lov Cust One');

        // Memilih group mengisi kolom label pendamping CUSTGROUPDESC.
        $component->set('form.CUSTGROUP', $code)
            ->assertSet('form.CUSTGROUPDESC', 'GRP-Lov Cust One');

        // Simpan -> kedua kolom tersimpan.
        $component->call('save')->assertHasNoErrors();

        $row = $manager->find('CUSTOMER', $custKey);
        expect($row['CUSTGROUP'])->toBe($code)
            ->and($row['CUSTGROUPDESC'])->toBe('GRP-Lov Cust One');
    } finally {
        // Pulihkan definisi LOV asli (@NEXUS) + kompilasi ulang + hapus data uji.
        $conn->update(
            "UPDATE DS_LOV SET VALUE_COLUMN = ?, LABEL_COLUMN = ?, SOURCE_QUERY = ? WHERE OBJECT_CODE = 'LOV_CUST_GROUP'",
            [$orig['VALUE_COLUMN'], $orig['LABEL_COLUMN'], $origSrc],
        );
        $compiler->compileOne($appId, $adminId);
        $manager->delete('CUSTOMER', $custKey);
    }
});
