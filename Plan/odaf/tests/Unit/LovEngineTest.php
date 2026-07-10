<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Odaf\Compiler\RuntimePackage;
use Odaf\Engine\Lov\OracleLovEngine;
use Odaf\Runtime\ExecutionContext;
use Odaf\Runtime\ServiceRegistry;
use Odaf\Runtime\UnifiedRuntimeKernel;

afterEach(function (): void {
    Mockery::close();
});

/**
 * Bangun LovEngine dengan package berisi kumpulan LOV STATIC (tanpa DB).
 */
function lovEngineWith(array $lovs): array
{
    $payload = [
        'application' => ['id' => 'APP1', 'code' => 'X', 'name' => 'X'],
        'menus' => [], 'pages' => [], 'datasets' => [], 'rules' => [],
        'workflows' => [], 'notifications' => [], 'subscriptions' => [],
        'index' => [],
        'lovs' => $lovs,
    ];
    $package = new RuntimePackage('PKG1', '1.x', 'APP1', 'sum', $payload);
    $kernel = new UnifiedRuntimeKernel(new ServiceRegistry);
    $kernel->loadPackage($package);

    // STATIC tidak menyentuh DB.
    $engine = new OracleLovEngine(Mockery::mock(ConnectionInterface::class), $kernel);
    $context = new ExecutionContext(applicationId: 'APP1');

    return [$engine, $context];
}

function staticLov(string $id, string $json): array
{
    return [
        'id' => $id, 'code' => $id, 'lovType' => 'STATIC',
        'valueColumn' => null, 'labelColumn' => null, 'sourceQuery' => $json,
    ];
}

it('mem-parse LOV STATIC array objek {value,label}', function (): void {
    [$engine, $ctx] = lovEngineWith([
        'L1' => staticLov('L1', '[{"value":"R","label":"Regular"},{"value":"V","label":"VIP"}]'),
    ]);

    expect($engine->options($ctx, 'L1'))->toBe([
        ['value' => 'R', 'label' => 'Regular'],
        ['value' => 'V', 'label' => 'VIP'],
    ]);
});

it('mem-parse LOV STATIC map {value:label}', function (): void {
    [$engine, $ctx] = lovEngineWith([
        'L2' => staticLov('L2', '{"A":"Aktif","N":"Nonaktif"}'),
    ]);

    expect($engine->options($ctx, 'L2'))->toBe([
        ['value' => 'A', 'label' => 'Aktif'],
        ['value' => 'N', 'label' => 'Nonaktif'],
    ]);
});

it('mem-parse LOV STATIC array skalar (value=label)', function (): void {
    [$engine, $ctx] = lovEngineWith([
        'L3' => staticLov('L3', '["Kecil","Sedang","Besar"]'),
    ]);

    expect($engine->options($ctx, 'L3'))->toBe([
        ['value' => 'Kecil', 'label' => 'Kecil'],
        ['value' => 'Sedang', 'label' => 'Sedang'],
        ['value' => 'Besar', 'label' => 'Besar'],
    ]);
});

it('memetakan nilai ke label & mengembalikan kosong untuk LOV tak dikenal', function (): void {
    [$engine, $ctx] = lovEngineWith([
        'L1' => staticLov('L1', '[{"value":"V","label":"VIP"}]'),
    ]);

    expect($engine->label($ctx, 'L1', 'V'))->toBe('VIP')
        ->and($engine->label($ctx, 'L1', 'ZZ'))->toBeNull()
        ->and($engine->options($ctx, 'NOPE'))->toBe([]);
});

it('LOV SQL dependen mengembalikan kosong bila parameter {{TOKEN}} belum tersedia', function (): void {
    // Connection di-mock tanpa ekspektasi: query TIDAK boleh dijalankan bila
    // parameter dependen belum ada.
    [$engine, $ctx] = lovEngineWith([
        'LSQL' => [
            'id' => 'LSQL', 'code' => 'LSQL', 'lovType' => 'SQL',
            'valueColumn' => 'V', 'labelColumn' => 'L',
            'sourceQuery' => 'SELECT a AS V, b AS L FROM t WHERE c = {{CUSTOMER_CODE}}',
        ],
    ]);

    expect($engine->options($ctx, 'LSQL', []))->toBe([])
        ->and($engine->options($ctx, 'LSQL', ['CUSTOMER_CODE' => '']))->toBe([]);
});
