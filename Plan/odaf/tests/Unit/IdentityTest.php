<?php

declare(strict_types=1);

use Odaf\Support\Identity\UlidIdentityGenerator;

it('membangkitkan identitas HEX 32 karakter (RAW(16))', function (): void {
    $id = (new UlidIdentityGenerator)->generate();

    expect($id)->toHaveLength(32)
        ->and($id)->toMatch('/^[0-9A-F]{32}$/');
});

it('mengonversi HEX ke 16 byte biner dan kembali (roundtrip)', function (): void {
    $gen = new UlidIdentityGenerator;
    $id = $gen->generate();

    $binary = $gen->toBinary($id);

    expect(strlen($binary))->toBe(16)
        ->and($gen->fromBinary($binary))->toBe($id);
});

it('menghasilkan identitas yang terurut waktu (sortable)', function (): void {
    $gen = new UlidIdentityGenerator;
    $a = $gen->generate();
    usleep(2000);
    $b = $gen->generate();

    expect(strcmp($a, $b))->toBeLessThan(0);
});
