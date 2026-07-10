<?php

declare(strict_types=1);

namespace Odaf\Compiler\Contracts;

/**
 * Runtime Package — artefak keluaran compiler yang immutable.
 *
 * Disimpan pada Runtime Repository (RT_*) dan menjadi satu-satunya sumber yang
 * dieksekusi runtime kernel (CORE-002, CORE-003).
 *
 * Referensi: Volume 2 Bab 32 (Runtime Repository), Volume 3 Bab 07.
 */
interface RuntimePackageInterface
{
    /** Identitas package. */
    public function packageId(): string;

    /** Versi package (semver / incremental). */
    public function packageVersion(): string;

    /** OBJECT_ID aplikasi asal — identitas dipertahankan (Vol.2 Bab 14). */
    public function applicationId(): string;

    /**
     * Checksum deterministik dari isi package untuk verifikasi reproducibility.
     */
    public function checksum(): string;

    /**
     * Objek runtime terkompilasi (RT_PAGE, RT_DATASET, dst) dalam bentuk siap
     * dieksekusi.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
