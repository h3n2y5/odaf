<?php

declare(strict_types=1);

use Odaf\Compiler\Contracts\CompilationException;
use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;
use Odaf\Compiler\MetadataCompiler;
use Odaf\Support\Identity\UlidIdentityGenerator;
use Tests\Support\GraphFactory;

function compiler(): MetadataCompiler
{
    return new MetadataCompiler(new UlidIdentityGenerator);
}

it('mengompilasi graph valid menjadi runtime package', function (): void {
    $package = compiler()->compile(GraphFactory::valid());

    expect($package->applicationId())->toBe('APP0000000000000000000000000010')
        ->and($package->checksum())->toHaveLength(64)
        ->and($package->toArray())->toHaveKeys(['application', 'menus', 'pages', 'datasets', 'rules', 'index']);
});

it('bersifat deterministik: metadata identik menghasilkan checksum & versi identik', function (): void {
    $a = compiler()->compile(GraphFactory::valid());
    $b = compiler()->compile(GraphFactory::valid());

    expect($a->checksum())->toBe($b->checksum())
        ->and($a->packageVersion())->toBe($b->packageVersion());
});

it('menghasilkan pohon menu dengan referensi halaman', function (): void {
    $payload = compiler()->compile(GraphFactory::valid())->toArray();

    expect($payload['menus'])->toHaveCount(1)
        ->and($payload['menus'][0]['code'])->toBe('MENU_CUSTOMER')
        ->and($payload['menus'][0]['pageId'])->toBe('PG00000000000000000000000000030');
});

it('mengindeks aturan validasi per dataset', function (): void {
    $payload = compiler()->compile(GraphFactory::valid())->toArray();
    $rules = $payload['rules']['DS00000000000000000000000000020'] ?? [];

    expect($rules)->toHaveCount(1)
        ->and($rules[0]['ruleType'])->toBe('REQUIRED')
        ->and($rules[0]['column'])->toBe('CUSTOMER_CODE');
});

it('gagal kompilasi ketika halaman mereferensi dataset tak dikenal', function (): void {
    compiler()->compile(GraphFactory::brokenDatasetRef());
})->throws(CompilationException::class);

it('validate() melaporkan diagnostik error tanpa melempar', function (): void {
    $diagnostics = compiler()->validate(GraphFactory::brokenDatasetRef());

    $errors = array_filter(
        $diagnostics,
        static fn (CompilerDiagnosticInterface $d): bool => $d->severity() === CompilerDiagnosticInterface::SEVERITY_ERROR,
    );

    expect($errors)->not->toBeEmpty();
});
