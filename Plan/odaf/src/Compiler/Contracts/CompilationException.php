<?php

declare(strict_types=1);

namespace Odaf\Compiler\Contracts;

use RuntimeException;

/**
 * Dilempar ketika kompilasi metadata gagal.
 *
 * Membawa daftar diagnostik agar caller (CLI/Studio) dapat menampilkan seluruh
 * error sekaligus.
 */
final class CompilationException extends RuntimeException
{
    /**
     * @param  array<int, CompilerDiagnosticInterface>  $diagnostics
     */
    public function __construct(
        string $message,
        private readonly array $diagnostics = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<int, CompilerDiagnosticInterface>
     */
    public function diagnostics(): array
    {
        return $this->diagnostics;
    }
}
