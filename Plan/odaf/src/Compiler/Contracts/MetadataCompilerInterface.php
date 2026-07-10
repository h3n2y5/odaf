<?php

declare(strict_types=1);

namespace Odaf\Compiler\Contracts;

use Odaf\Metadata\Contracts\MetadataObjectInterface;

/**
 * BB-02 Metadata Compiler.
 *
 * Mentransformasi metadata design-time yang dapat diedit menjadi Runtime Package
 * yang immutable dan deterministik.
 *
 * Pipeline (Vol.3 Bab 03-07):
 *   parse -> validate -> semantic analysis -> dependency resolution
 *         -> MIR (Metadata Intermediate Representation) -> optimize -> backend
 *
 * Kontrak wajib:
 *  - Determinisme: metadata identik -> Runtime Package identik (CORE-005).
 *  - Kegagalan validasi -> kompilasi gagal (tidak menghasilkan package parsial).
 */
interface MetadataCompilerInterface
{
    /**
     * Kompilasi graph metadata sebuah aplikasi menjadi Runtime Package.
     *
     * @throws CompilationException bila validasi gagal.
     */
    public function compile(MetadataObjectInterface $applicationGraph): RuntimePackageInterface;

    /**
     * Validasi metadata tanpa menghasilkan package (dry-run untuk Studio).
     * Mengembalikan daftar diagnostik (error/warning).
     *
     * @return array<int, CompilerDiagnosticInterface>
     */
    public function validate(MetadataObjectInterface $applicationGraph): array;
}
