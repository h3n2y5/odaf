<?php

declare(strict_types=1);

namespace Odaf\Engine\Validation;

use Odaf\Engine\Validation\Contracts\ValidationResultInterface;

/**
 * Hasil evaluasi validasi metadata-driven.
 */
final class ValidationResult implements ValidationResultInterface
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(private readonly array $errors = []) {}

    public function passes(): bool
    {
        return $this->errors === [];
    }

    public function fails(): bool
    {
        return ! $this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Seluruh pesan error diratakan menjadi list.
     *
     * @return array<int, string>
     */
    public function messages(): array
    {
        $out = [];
        foreach ($this->errors as $messages) {
            foreach ($messages as $m) {
                $out[] = $m;
            }
        }

        return $out;
    }
}
