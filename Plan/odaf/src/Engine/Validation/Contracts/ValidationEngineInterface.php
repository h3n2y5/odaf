<?php

declare(strict_types=1);

namespace Odaf\Engine\Validation\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-09 Validation Engine (Vol.3 Bab 13 - Rule Engine).
 *
 * Mengevaluasi aturan validasi metadata-driven (VAL_*) sebelum eksekusi dataset.
 * Tidak ada validasi yang di-hardcode per form.
 *
 * Kategori: field, record, dataset, workflow. Tipe: required, min/max length,
 * min/max value, regex, unique, foreign key, formula, conditional.
 */
interface ValidationEngineInterface
{
    /**
     * Validasi payload terhadap seluruh rule yang terkait sebuah dataset/form.
     *
     * @param  array<string, mixed>  $data
     */
    public function validate(
        ExecutionContextInterface $context,
        string $datasetId,
        array $data,
        string $operation = 'CREATE',
    ): ValidationResultInterface;
}
