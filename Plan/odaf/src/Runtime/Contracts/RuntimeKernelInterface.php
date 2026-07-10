<?php

declare(strict_types=1);

namespace Odaf\Runtime\Contracts;

use Odaf\Compiler\Contracts\RuntimePackageInterface;

/**
 * BB-03 Unified Runtime Kernel (URK).
 *
 * Mengeksekusi Runtime Package terkompilasi. Kernel TIDAK boleh membaca metadata
 * design-time (CORE-002).
 *
 * Pipeline eksekusi (Vol.3 Bab 10):
 *   Resolve Context -> Locate Package -> Resolve Service
 *                   -> Execute Object Graph -> Generate Response -> Audit
 */
interface RuntimeKernelInterface
{
    /**
     * Muat Runtime Package aktif untuk sebuah aplikasi ke dalam kernel cache.
     */
    public function loadPackage(RuntimePackageInterface $package): void;

    /**
     * Eksekusi sebuah objek runtime (mis. membuka halaman, menjalankan aksi)
     * dalam konteks eksekusi tertentu.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function execute(ExecutionContextInterface $context, string $runtimeObjectId, array $input = []): array;

    /**
     * Resolusi service platform terdaftar (dataset, security, validation, dst)
     * melalui Service Registry (Vol.3 Bab 09).
     *
     * @template T of object
     *
     * @param  class-string<T>  $serviceContract
     * @return T
     */
    public function resolve(string $serviceContract): object;
}
