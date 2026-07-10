<?php

declare(strict_types=1);

namespace Odaf\Runtime;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * Konteks eksekusi immutable untuk satu siklus request (Vol.3 Bab 10).
 */
final class ExecutionContext implements ExecutionContextInterface
{
    /**
     * @param  array<int, string>  $roleIds
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        private readonly string $applicationId,
        private readonly ?string $userId = null,
        private readonly string $locale = 'id',
        private readonly array $roleIds = [],
        private readonly array $attributes = [],
    ) {}

    public function userId(): ?string
    {
        return $this->userId;
    }

    public function applicationId(): string
    {
        return $this->applicationId;
    }

    public function locale(): string
    {
        return $this->locale;
    }

    public function roleIds(): array
    {
        return $this->roleIds;
    }

    public function attributes(): array
    {
        return $this->attributes;
    }

    /**
     * Kembalikan salinan context dengan atribut tambahan.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function withAttributes(array $attributes): self
    {
        return new self(
            $this->applicationId,
            $this->userId,
            $this->locale,
            $this->roleIds,
            [...$this->attributes, ...$attributes],
        );
    }
}
