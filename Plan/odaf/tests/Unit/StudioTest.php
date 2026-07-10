<?php

declare(strict_types=1);

use Illuminate\Database\ConnectionInterface;
use Odaf\Studio\TableDataManager;
use Odaf\Studio\TableIntrospector;

afterEach(function (): void {
    Mockery::close();
});

it('encode & decode key (roundtrip) untuk PK tunggal & komposit', function (): void {
    $single = ['OBJECT_ID' => 'AABBCC'];
    $composite = ['ROLE_ID' => 'R1', 'PERMISSION_ID' => 'P1'];

    $tokenSingle = TableDataManager::encodeKey($single);
    $tokenComposite = TableDataManager::encodeKey($composite);

    expect(TableDataManager::decodeKey($tokenSingle))->toBe($single)
        ->and(TableDataManager::decodeKey($tokenComposite))->toBe($composite)
        ->and($tokenSingle)->not->toContain('=')
        ->and($tokenSingle)->not->toContain('/');
});

it('menghasilkan token key URL-safe', function (): void {
    $token = TableDataManager::encodeKey(['OBJECT_ID' => str_repeat('F', 32)]);

    expect($token)->toMatch('/^[A-Za-z0-9_-]+$/');
});

it('mengelompokkan tabel berdasarkan prefiks domain', function (): void {
    $introspector = new TableIntrospector(Mockery::mock(ConnectionInterface::class));

    expect($introspector->groupOf('APP_MENU'))->toBe('Metadata')
        ->and($introspector->groupOf('UI_FIELD'))->toBe('Metadata')
        ->and($introspector->groupOf('DS_LOV'))->toBe('Metadata')
        ->and($introspector->groupOf('WF_WORKFLOW'))->toBe('Metadata')
        ->and($introspector->groupOf('NTF_SUBSCRIPTION'))->toBe('Metadata')
        ->and($introspector->groupOf('RT_PACKAGE'))->toBe('Runtime')
        ->and($introspector->groupOf('SEC_USER'))->toBe('Security')
        ->and($introspector->groupOf('AUD_EVENT'))->toBe('Audit')
        ->and($introspector->groupOf('SYS_STATUS'))->toBe('System')
        ->and($introspector->groupOf('CUSTOMER'))->toBe('Business')
        ->and($introspector->groupOf('PRODUCT'))->toBe('Business');
});
