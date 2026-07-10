<?php

declare(strict_types=1);

namespace Odaf\Compiler\Contracts;

/**
 * Diagnostik yang dihasilkan compiler saat validasi/kompilasi.
 *
 * Digunakan oleh ODAF Studio (BB-15) untuk menampilkan error/warning metadata.
 */
interface CompilerDiagnosticInterface
{
    public const SEVERITY_ERROR = 'ERROR';

    public const SEVERITY_WARNING = 'WARNING';

    public const SEVERITY_INFO = 'INFO';

    /** Salah satu dari SEVERITY_*. */
    public function severity(): string;

    /** Kode diagnostik stabil, mis. "ODAF-CMP-1001". */
    public function code(): string;

    /** Pesan deskriptif. */
    public function message(): string;

    /** OBJECT_ID metadata yang memicu diagnostik (bila ada). */
    public function objectId(): ?string;
}
