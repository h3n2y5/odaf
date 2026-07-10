<?php

declare(strict_types=1);

use Odaf\Studio\ColumnMapper;

function mapType(string $name, string $type, ?int $length = null, ?int $precision = null, ?int $scale = null): array
{
    return ColumnMapper::map([
        'name' => $name,
        'dataType' => $type,
        'length' => $length,
        'precision' => $precision,
        'scale' => $scale,
    ]);
}

it('memetakan VARCHAR2 pendek ke TEXT', function (): void {
    expect(mapType('PRODUCT_NAME', 'VARCHAR2', 200))
        ->toBe(['fieldType' => 'TEXT', 'dataType' => 'STRING']);
});

it('memetakan VARCHAR2 panjang (>255) ke TEXTAREA', function (): void {
    expect(mapType('DESCRIPTION', 'VARCHAR2', 1000))
        ->toBe(['fieldType' => 'TEXTAREA', 'dataType' => 'STRING']);
});

it('memetakan kolom berisi EMAIL ke EMAIL', function (): void {
    expect(mapType('CONTACT_EMAIL', 'VARCHAR2', 100)['fieldType'])->toBe('EMAIL');
});

it('memetakan CLOB ke TEXTAREA', function (): void {
    expect(mapType('NOTES', 'CLOB')['fieldType'])->toBe('TEXTAREA');
});

it('memetakan NUMBER berskala ke DECIMAL, tanpa skala ke INTEGER', function (): void {
    expect(mapType('UNIT_PRICE', 'NUMBER', null, 15, 2))
        ->toBe(['fieldType' => 'NUMBER', 'dataType' => 'DECIMAL'])
        ->and(mapType('STOCK_QTY', 'NUMBER', null, 10, 0))
        ->toBe(['fieldType' => 'NUMBER', 'dataType' => 'INTEGER']);
});

it('memetakan NUMBER(1) ke CHECKBOX/BOOLEAN', function (): void {
    expect(mapType('IS_ENABLED', 'NUMBER', null, 1, 0))
        ->toBe(['fieldType' => 'CHECKBOX', 'dataType' => 'BOOLEAN']);
});

it('memetakan DATE dan TIMESTAMP', function (): void {
    expect(mapType('ORDER_DATE', 'DATE')['fieldType'])->toBe('DATE')
        ->and(mapType('CREATED_AT', 'TIMESTAMP(6)')['fieldType'])->toBe('DATETIME');
});

it('mengenali kolom sistem & biner', function (): void {
    expect(ColumnMapper::isSystemColumn('CREATED_AT'))->toBeTrue()
        ->and(ColumnMapper::isSystemColumn('VERSION_NO'))->toBeTrue()
        ->and(ColumnMapper::isSystemColumn('PRODUCT_NAME'))->toBeFalse()
        ->and(ColumnMapper::isBinary('RAW'))->toBeTrue();
});

it('menghumanisasi nama kolom', function (): void {
    expect(ColumnMapper::humanize('PRODUCT_CODE'))->toBe('Product Code')
        ->and(ColumnMapper::humanize('UNIT_PRICE'))->toBe('Unit Price');
});
