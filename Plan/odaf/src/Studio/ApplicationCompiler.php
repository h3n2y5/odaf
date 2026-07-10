<?php

declare(strict_types=1);

namespace Odaf\Studio;

use Odaf\Compiler\Contracts\MetadataCompilerInterface;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Odaf\Runtime\RuntimePackageRepository;
use Throwable;

/**
 * Orkestrasi kompilasi aplikasi untuk ODAF Studio.
 *
 * Setelah metadata disunting via Studio, package harus dikompilasi ulang &
 * diaktifkan agar runtime menampilkannya (CORE-002: runtime hanya membaca
 * package terkompilasi, bukan tabel metadata langsung).
 */
final class ApplicationCompiler
{
    public function __construct(
        private readonly MetadataRepositoryInterface $repository,
        private readonly MetadataCompilerInterface $compiler,
        private readonly RuntimePackageRepository $packages,
    ) {}

    /**
     * Kompilasi & aktifkan seluruh aplikasi.
     *
     * @return array{compiled: array<int, string>, failed: array<string, string>}
     */
    public function compileAll(?string $compiledBy = null): array
    {
        $compiled = [];
        $failed = [];

        foreach ($this->repository->allInDomain('APP') as $app) {
            try {
                $this->compileOne($app->objectId(), $compiledBy);
                $compiled[] = $app->objectCode();
            } catch (Throwable $e) {
                $failed[$app->objectCode()] = $this->firstLine($e->getMessage());
            }
        }

        return ['compiled' => $compiled, 'failed' => $failed];
    }

    /**
     * Kompilasi & aktifkan satu aplikasi berdasarkan OBJECT_ID.
     */
    public function compileOne(string $applicationId, ?string $compiledBy = null): string
    {
        $graph = $this->repository->loadApplicationGraph($applicationId);
        $package = $this->compiler->compile($graph);
        $this->packages->store($package, $compiledBy);
        $this->packages->activate($package->applicationId(), $package->packageVersion());

        return $package->packageVersion();
    }

    private function firstLine(string $text): string
    {
        $line = strtok($text, "\n");

        return $line === false ? $text : $line;
    }
}
