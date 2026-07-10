<?php

declare(strict_types=1);

use App\Auth\OdafUser;
use Illuminate\Support\Facades\DB;
use Odaf\Studio\ApplicationCompiler;
use Odaf\Studio\TableDataManager;

/**
 * End-to-end LOV: buat DS_LOV (STATIC) via Studio, assign ke UI_FIELD, kompilasi,
 * lalu form runtime menampilkan dropdown ber-opsi. Dibersihkan setelahnya.
 */
function lovAdmin(): OdafUser
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

it('LOV mengisi dropdown di form runtime setelah di-assign & dikompilasi', function (): void {
    $manager = app(TableDataManager::class);
    $compiler = app(ApplicationCompiler::class);
    $conn = DB::connection((string) config('database.default'));
    $appId = '00000000000000000000000000000010';

    // Field EMAIL pada halaman Customer dipakai sebagai target LOV.
    $fieldId = (string) $conn->scalar(
        "SELECT RAWTOHEX(f.OBJECT_ID) FROM UI_FIELD f
         JOIN UI_PAGE p ON p.OBJECT_ID = f.PAGE_ID
         WHERE p.OBJECT_CODE = 'PAGE_CUSTOMER' AND f.COLUMN_NAME = 'EMAIL'"
    );
    expect($fieldId)->not->toBe('');

    // 1. Buat LOV STATIC via Data Manager.
    $code = 'LOV_RT_'.substr((string) hrtime(true), -8);
    $lovKey = $manager->create('DS_LOV', [
        'OBJECT_CODE' => $code,
        'OBJECT_NAME' => 'LOV Runtime Test',
        'LOV_TYPE' => 'STATIC',
        'SOURCE_QUERY' => '[{"value":"GOLD","label":"Emas"},{"value":"SILVER","label":"Perak"}]',
        'STATUS' => 'PUBLISHED',
    ], userId: '00000000000000000000000000000001');
    $lovId = $lovKey['OBJECT_ID'];

    // 2. Assign LOV ke field + kompilasi.
    $manager->update('UI_FIELD', ['OBJECT_ID' => $fieldId], ['LOV_ID' => $lovId], userId: '00000000000000000000000000000001');
    $compiler->compileOne($appId, '00000000000000000000000000000001');

    // 3. Form runtime menampilkan opsi LOV.
    $this->actingAs(lovAdmin())
        ->get('/app/ODAF_DEMO/f/PAGE_CUSTOMER')
        ->assertOk()
        ->assertSee('Emas')
        ->assertSee('Perak');

    // 4. Bersihkan: lepas LOV dari field, hapus LOV, kompilasi ulang.
    $manager->update('UI_FIELD', ['OBJECT_ID' => $fieldId], ['LOV_ID' => null], userId: '00000000000000000000000000000001');
    $manager->delete('DS_LOV', $lovKey);
    $compiler->compileOne($appId, '00000000000000000000000000000001');
});
