<?php

declare(strict_types=1);

use App\Auth\OdafUser;
use Odaf\Runtime\RuntimePackageRepository;
use Odaf\Studio\ApplicationCompiler;
use Odaf\Studio\TableDataManager;

/**
 * Smoke test ODAF Studio Data Manager terhadap Oracle: rendering halaman
 * (admin-gated) + CRUD generik berbasis introspeksi pada tabel metadata.
 */
function studioAdmin(): OdafUser
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

function nonAdmin(): OdafUser
{
    return new OdafUser(
        objectId: '000000000000000000000000000000AA',
        username: 'staff',
        name: 'Staff',
        email: null,
        passwordHash: null,
        roleIds: [],
    );
}

/**
 * Ratakan pohon menu package menjadi daftar OBJECT_CODE.
 *
 * @param  array<int, array<string, mixed>>  $menus
 * @return array<int, string>
 */
function menuCodes(array $menus): array
{
    $codes = [];
    foreach ($menus as $menu) {
        $codes[] = (string) ($menu['code'] ?? '');
        foreach (menuCodes($menu['children'] ?? []) as $child) {
            $codes[] = $child;
        }
    }

    return $codes;
}

it('menampilkan katalog tabel Studio untuk admin', function (): void {
    $this->actingAs(studioAdmin())
        ->get('/studio')
        ->assertOk()
        ->assertSee('Data Manager')
        ->assertSee('DS_DATASET');
});

it('menolak non-admin membuka Studio (403)', function (): void {
    $this->actingAs(nonAdmin())
        ->get('/studio')
        ->assertForbidden();
});

it('merender grid tabel metadata dengan data yang ada', function (): void {
    $this->actingAs(studioAdmin())
        ->get('/studio/t/DS_DATASET')
        ->assertOk()
        ->assertSee('DS_CUSTOMER'); // dataset seed
});

it('merender form generik dengan kolom tabel', function (): void {
    $this->actingAs(studioAdmin())
        ->get('/studio/t/DS_LOV')
        ->assertOk();

    $this->actingAs(studioAdmin())
        ->get('/studio/t/DS_LOV/edit')
        ->assertOk()
        ->assertSee('OBJECT_CODE')
        ->assertSee('LOV_TYPE');
});

it('menampilkan flag & status sebagai dropdown, dan PAGE_ID sebagai FK dropdown', function (): void {
    $this->actingAs(studioAdmin())
        ->get('/studio/t/APP_MENU/edit')
        ->assertOk()
        ->assertSee('Ya (1)')          // VISIBLE_FLAG -> pilihan flag
        ->assertSee('PUBLISHED')       // STATUS -> pilihan enum
        ->assertSee('Customer Form');  // PAGE_ID (FK) -> opsi UI_PAGE by OBJECT_NAME
});

it('recompile via Studio mengaktifkan aplikasi', function (): void {
    /** @var ApplicationCompiler $compiler */
    $compiler = app(ApplicationCompiler::class);

    $result = $compiler->compileAll('00000000000000000000000000000001');

    expect($result['failed'])->toBe([])
        ->and($result['compiled'])->toContain('ODAF_DEMO');
});

it('menu baru tampil di aplikasi setelah kompilasi ulang (alur end-to-end)', function (): void {
    $manager = app(TableDataManager::class);
    $compiler = app(ApplicationCompiler::class);
    $packages = app(RuntimePackageRepository::class);
    $appId = '00000000000000000000000000000010';

    $code = 'MENU_TEST_'.substr((string) hrtime(true), -8);
    $key = $manager->create('APP_MENU', [
        'MODULE_ID' => '00000000000000000000000000000011',
        'PAGE_ID' => '00000000000000000000000000000030',
        'OBJECT_CODE' => $code,
        'OBJECT_NAME' => 'Menu Test',
        'VISIBLE_FLAG' => '1',
        'DISPLAY_ORDER' => '99',
        'STATUS' => 'PUBLISHED',
    ], userId: '00000000000000000000000000000001');

    // Sebelum recompile: belum ada di package aktif.
    $before = menuCodes($packages->loadActiveByCode('ODAF_DEMO')?->toArray()['menus'] ?? []);
    expect($before)->not->toContain($code);

    // Recompile -> tampil.
    $compiler->compileOne($appId, '00000000000000000000000000000001');
    $after = menuCodes($packages->loadActiveByCode('ODAF_DEMO')?->toArray()['menus'] ?? []);
    expect($after)->toContain($code);

    // Bersihkan + recompile agar package kembali bersih.
    $manager->delete('APP_MENU', $key);
    $compiler->compileOne($appId, '00000000000000000000000000000001');
});

it('menu tanpa halaman: link konfigurasi untuk admin, teks biasa untuk non-admin', function (): void {
    $manager = app(TableDataManager::class);
    $compiler = app(ApplicationCompiler::class);
    $appId = '00000000000000000000000000000010';

    $code = 'MENU_NOPAGE_'.substr((string) hrtime(true), -8);
    $key = $manager->create('APP_MENU', [
        'MODULE_ID' => '00000000000000000000000000000011',
        'OBJECT_CODE' => $code,
        'OBJECT_NAME' => 'Menu Tanpa Halaman',
        'VISIBLE_FLAG' => '1',
        'DISPLAY_ORDER' => '5',
        'STATUS' => 'PUBLISHED',
    ], userId: '00000000000000000000000000000001');
    $compiler->compileOne($appId, '00000000000000000000000000000001');

    // Admin: tampil link konfigurasi ke form Studio APP_MENU.
    $this->actingAs(studioAdmin())
        ->get('/app/ODAF_DEMO')
        ->assertOk()
        ->assertSee('Menu Tanpa Halaman')
        ->assertSee('studio/t/APP_MENU/edit', false);

    // Non-admin: teks biasa tanpa link konfigurasi.
    $this->actingAs(nonAdmin())
        ->get('/app/ODAF_DEMO')
        ->assertOk()
        ->assertSee('tanpa halaman')
        ->assertDontSee('studio/t/APP_MENU/edit', false);

    // Bersihkan + recompile agar package kembali bersih.
    $manager->delete('APP_MENU', $key);
    $compiler->compileOne($appId, '00000000000000000000000000000001');
});

it('membuat, menemukan, dan menghapus baris via CRUD generik (RAW(16) PK otomatis)', function (): void {
    /** @var TableDataManager $manager */
    $manager = app(TableDataManager::class);

    $code = 'LOV_TEST_'.substr((string) hrtime(true), -8);
    $key = $manager->create('DS_LOV', [
        'OBJECT_CODE' => $code,
        'OBJECT_NAME' => 'Studio Test LOV',
        'LOV_TYPE' => 'STATIC',
        'STATUS' => 'PUBLISHED',
    ], userId: '00000000000000000000000000000001');

    expect($key)->toHaveKey('OBJECT_ID')
        ->and($key['OBJECT_ID'])->toHaveLength(32);

    $row = $manager->find('DS_LOV', $key);
    expect($row)->not->toBeNull()
        ->and($row['OBJECT_CODE'])->toBe($code)
        ->and($row['LOV_TYPE'])->toBe('STATIC');

    // Bersihkan.
    $manager->delete('DS_LOV', $key);
    expect($manager->find('DS_LOV', $key))->toBeNull();
});
