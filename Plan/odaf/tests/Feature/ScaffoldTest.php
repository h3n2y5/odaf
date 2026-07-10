<?php

declare(strict_types=1);

use App\Auth\OdafUser;
use Odaf\Studio\MetadataScaffolder;

/**
 * Smoke test Metadata Scaffolder (ODAF Studio) terhadap Oracle. Membutuhkan
 * tabel contoh PRODUCT (dibuat oleh 10_sample_product.sql) + package aktif.
 */
function scaffoldAdmin(): OdafUser
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

it('menghasilkan metadata lengkap dari tabel PRODUCT yang sudah ada', function (): void {
    /** @var MetadataScaffolder $scaffolder */
    $scaffolder = app(MetadataScaffolder::class);

    $result = $scaffolder->scaffold([
        'table' => 'PRODUCT',
        'appCode' => 'ODAF_DEMO',
        'label' => 'Produk',
        'force' => true,
    ]);

    // 5 field bisnis (PK + audit + ACTIVE_FLAG dikecualikan), 8 aturan validasi.
    expect($result['datasetCode'])->toBe('DS_PRODUCT')
        ->and($result['pageCode'])->toBe('PAGE_PRODUCT')
        ->and($result['menuCode'])->toBe('MENU_PRODUCT')
        ->and($result['primaryKey'])->toBe('PRODUCT_ID')
        ->and($result['softDelete'])->toBeTrue()
        ->and($result['fields'])->toBe(5)
        ->and($result['rules'])->toBe(8);
});

it('metadata hasil scaffold tersimpan di repository', function (): void {
    $connection = DB::connection((string) config('database.default'));

    $dsCount = (int) $connection->scalar("SELECT COUNT(*) FROM DS_DATASET WHERE OBJECT_CODE = 'DS_PRODUCT'");
    $pageCount = (int) $connection->scalar("SELECT COUNT(*) FROM UI_PAGE WHERE OBJECT_CODE = 'PAGE_PRODUCT'");
    $fieldCount = (int) $connection->scalar(
        "SELECT COUNT(*) FROM UI_FIELD WHERE PAGE_ID IN (SELECT OBJECT_ID FROM UI_PAGE WHERE OBJECT_CODE = 'PAGE_PRODUCT')"
    );

    expect($dsCount)->toBe(1)
        ->and($pageCount)->toBe(1)
        ->and($fieldCount)->toBe(5);
});

it('form & grid PRODUCT hasil scaffold ter-render di runtime', function (): void {
    // Package aktif sudah menyertakan PRODUCT (dikompilasi saat scaffold --compile).
    $this->actingAs(scaffoldAdmin())
        ->get('/app/ODAF_DEMO/g/PAGE_PRODUCT')
        ->assertOk()
        ->assertSee('Produk');

    $this->actingAs(scaffoldAdmin())
        ->get('/app/ODAF_DEMO/f/PAGE_PRODUCT')
        ->assertOk()
        ->assertSee('Product Code')
        ->assertSee('Unit Price');
});
