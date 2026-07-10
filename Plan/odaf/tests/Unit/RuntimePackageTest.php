<?php

declare(strict_types=1);

use Odaf\Compiler\RuntimePackage;

it('menghasilkan JSON kanonik dengan kunci terurut secara rekursif', function (): void {
    $a = RuntimePackage::canonicalJson(['b' => 1, 'a' => ['y' => 2, 'x' => 1]]);
    $b = RuntimePackage::canonicalJson(['a' => ['x' => 1, 'y' => 2], 'b' => 1]);

    expect($a)->toBe($b)
        ->and($a)->toBe('{"a":{"x":1,"y":2},"b":1}');
});

it('mempertahankan urutan elemen list numerik', function (): void {
    $json = RuntimePackage::canonicalJson(['items' => [3, 1, 2]]);

    expect($json)->toBe('{"items":[3,1,2]}');
});

it('mengekspos identitas & checksum package', function (): void {
    $package = new RuntimePackage(
        packageId: 'PKG1',
        packageVersion: '1.abc',
        applicationId: 'APP1',
        checksum: 'deadbeef',
        payload: ['application' => ['code' => 'X']],
    );

    expect($package->packageId())->toBe('PKG1')
        ->and($package->packageVersion())->toBe('1.abc')
        ->and($package->applicationId())->toBe('APP1')
        ->and($package->checksum())->toBe('deadbeef')
        ->and($package->toCanonicalJson())->toBe('{"application":{"code":"X"}}');
});
