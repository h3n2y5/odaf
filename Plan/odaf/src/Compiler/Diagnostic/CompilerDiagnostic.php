<?php

declare(strict_types=1);

namespace Odaf\Compiler\Diagnostic;

use Odaf\Compiler\Contracts\CompilerDiagnosticInterface;

/**
 * Implementasi diagnostik compiler (error/warning/info).
 */
final class CompilerDiagnostic implements CompilerDiagnosticInterface
{
    public function __construct(
        private readonly string $severity,
        private readonly string $code,
        private readonly string $message,
        private readonly ?string $objectId = null,
    ) {}

    public static function error(string $code, string $message, ?string $objectId = null): self
    {
        return new self(self::SEVERITY_ERROR, $code, $message, $objectId);
    }

    public static function warning(string $code, string $message, ?string $objectId = null): self
    {
        return new self(self::SEVERITY_WARNING, $code, $message, $objectId);
    }

    public function severity(): string
    {
        return $this->severity;
    }

    public function code(): string
    {
        return $this->code;
    }

    public function message(): string
    {
        return $this->message;
    }

    public function objectId(): ?string
    {
        return $this->objectId;
    }

    public function isError(): bool
    {
        return $this->severity === self::SEVERITY_ERROR;
    }

    /**
     * @return array{severity: string, code: string, message: string, objectId: string|null}
     */
    public function toArray(): array
    {
        return [
            'severity' => $this->severity,
            'code' => $this->code,
            'message' => $this->message,
            'objectId' => $this->objectId,
        ];
    }
}
