<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Odaf\Compiler\RuntimePackage;
use Odaf\Engine\Validation\MetadataValidationEngine;
use Odaf\Runtime\ExecutionContext;
use Odaf\Runtime\ServiceRegistry;
use Odaf\Runtime\UnifiedRuntimeKernel;

/**
 * Membangun engine validasi dengan package yang memuat kumpulan rule tertentu.
 *
 * @param  array<int, array<string, mixed>>  $rules
 */
function validationEngineWith(array $rules): array
{
    $applicationId = 'APP1';
    $datasetId = 'DS1';

    $payload = [
        'application' => ['id' => $applicationId, 'code' => 'X', 'name' => 'X'],
        'menus' => [],
        'pages' => [],
        'lovs' => [],
        'index' => [],
        'datasets' => [
            $datasetId => [
                'id' => $datasetId,
                'code' => 'DS1',
                'sourceType' => 'TABLE',
                'sourceObject' => 'CUSTOMER',
                'primaryKey' => 'CUSTOMER_ID',
                'softDelete' => true,
            ],
        ],
        'rules' => [$datasetId => $rules],
    ];

    $package = new RuntimePackage('PKG1', '1.x', $applicationId, 'sum', $payload);
    $kernel = new UnifiedRuntimeKernel(new ServiceRegistry);
    $kernel->loadPackage($package);

    // Koneksi tidak dipakai untuk rule non-DB; mock tanpa ekspektasi.
    $connection = Mockery::mock(ConnectionInterface::class);

    $engine = new MetadataValidationEngine($kernel, $connection);
    $context = new ExecutionContext(applicationId: $applicationId);

    return [$engine, $context, $datasetId];
}

function rule(string $type, string $column, ?string $expr = null, string $message = 'err'): array
{
    return [
        'id' => 'R_'.$type,
        'code' => 'R_'.$type,
        'targetType' => 'FIELD',
        'ruleType' => $type,
        'expression' => $expr,
        'message' => $message,
        'fieldId' => 'F1',
        'column' => $column,
        'order' => 0,
    ];
}

afterEach(function (): void {
    Mockery::close();
});

it('REQUIRED gagal ketika nilai kosong', function (): void {
    [$engine, $context, $ds] = validationEngineWith([rule('REQUIRED', 'CUSTOMER_CODE', null, 'wajib')]);

    $result = $engine->validate($context, $ds, ['CUSTOMER_CODE' => '']);

    expect($result->fails())->toBeTrue()
        ->and($result->errors())->toHaveKey('CUSTOMER_CODE')
        ->and($result->errors()['CUSTOMER_CODE'][0])->toBe('wajib');
});

it('REQUIRED lulus ketika nilai ada', function (): void {
    [$engine, $context, $ds] = validationEngineWith([rule('REQUIRED', 'CUSTOMER_CODE')]);

    expect($engine->validate($context, $ds, ['CUSTOMER_CODE' => 'C001'])->passes())->toBeTrue();
});

it('MIN_LENGTH menegakkan panjang minimum', function (): void {
    [$engine, $context, $ds] = validationEngineWith([rule('MIN_LENGTH', 'CUSTOMER_CODE', '5', 'min 5')]);

    expect($engine->validate($context, $ds, ['CUSTOMER_CODE' => 'ABC'])->fails())->toBeTrue()
        ->and($engine->validate($context, $ds, ['CUSTOMER_CODE' => 'ABCDE'])->passes())->toBeTrue();
});

it('MAX_VALUE menegakkan nilai maksimum', function (): void {
    [$engine, $context, $ds] = validationEngineWith([rule('MAX_VALUE', 'CREDIT_LIMIT', '100', 'maks 100')]);

    expect($engine->validate($context, $ds, ['CREDIT_LIMIT' => 150])->fails())->toBeTrue()
        ->and($engine->validate($context, $ds, ['CREDIT_LIMIT' => 50])->passes())->toBeTrue();
});

it('REGEX memvalidasi format', function (): void {
    [$engine, $context, $ds] = validationEngineWith([rule('REGEX', 'EMAIL', '/^[^@]+@[^@]+$/', 'email tidak valid')]);

    expect($engine->validate($context, $ds, ['EMAIL' => 'bukan-email'])->fails())->toBeTrue()
        ->and($engine->validate($context, $ds, ['EMAIL' => 'a@b.com'])->passes())->toBeTrue();
});

it('mengumpulkan beberapa error sekaligus', function (): void {
    [$engine, $context, $ds] = validationEngineWith([
        rule('REQUIRED', 'CUSTOMER_CODE', null, 'kode wajib'),
        rule('REQUIRED', 'CUSTOMER_NAME', null, 'nama wajib'),
    ]);

    $result = $engine->validate($context, $ds, ['CUSTOMER_CODE' => '', 'CUSTOMER_NAME' => '']);

    expect($result->errors())->toHaveCount(2);
});
