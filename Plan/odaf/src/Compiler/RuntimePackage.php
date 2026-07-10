<?php

declare(strict_types=1);

namespace Odaf\Compiler;

use Odaf\Compiler\Contracts\RuntimePackageInterface;

/**
 * Artefak keluaran compiler yang immutable (BB-02 -> RT_PACKAGE).
 *
 * Payload berisi objek runtime terkompilasi (menu, page, dataset, rule) dalam
 * bentuk array siap eksekusi. Checksum dihitung dari serialisasi kanonik payload
 * sehingga metadata identik selalu menghasilkan checksum identik (CORE-005).
 */
final class RuntimePackage implements RuntimePackageInterface
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        private readonly string $packageId,
        private readonly string $packageVersion,
        private readonly string $applicationId,
        private readonly string $checksum,
        private readonly array $payload,
    ) {}

    public function packageId(): string
    {
        return $this->packageId;
    }

    public function packageVersion(): string
    {
        return $this->packageVersion;
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function checksum(): string
    {
        return $this->checksum;
    }

    public function toArray(): array
    {
        return $this->payload;
    }

    /**
     * Serialisasi kanonik payload (kunci terurut) untuk checksum & penyimpanan.
     */
    public function toCanonicalJson(): string
    {
        return self::canonicalJson($this->payload);
    }

    /**
     * Hasilkan JSON deterministik: kunci array asosiatif diurutkan rekursif.
     *
     * @param  array<string, mixed>  $data
     */
    public static function canonicalJson(array $data): string
    {
        $normalized = self::normalize($data);

        return (string) json_encode(
            $normalized,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
        );
    }

    /**
     * Urutkan kunci secara rekursif untuk determinisme.
     */
    private static function normalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        // List numerik: pertahankan urutan, normalisasi tiap elemen.
        if (array_is_list($value)) {
            return array_map(static fn ($v): mixed => self::normalize($v), $value);
        }

        ksort($value);
        $out = [];
        foreach ($value as $k => $v) {
            $out[$k] = self::normalize($v);
        }

        return $out;
    }
}
