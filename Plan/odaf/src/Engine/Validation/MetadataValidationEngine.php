<?php

declare(strict_types=1);

namespace Odaf\Engine\Validation;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Validation\Contracts\ValidationEngineInterface;
use Odaf\Engine\Validation\Contracts\ValidationResultInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\UnifiedRuntimeKernel;

/**
 * BB-09 Validation Engine (Vol.3 Bab 13).
 *
 * Mengevaluasi aturan validasi terkompilasi (dari package) terhadap payload
 * sebelum eksekusi dataset. Tidak ada validasi hardcoded per form.
 *
 * Tipe didukung F1: REQUIRED, MIN_LENGTH, MAX_LENGTH, MIN_VALUE, MAX_VALUE,
 * REGEX, UNIQUE (cek DB), FOREIGN_KEY (cek DB).
 */
final class MetadataValidationEngine implements ValidationEngineInterface
{
    public function __construct(
        private readonly UnifiedRuntimeKernel $kernel,
        private readonly ConnectionInterface $connection,
    ) {}

    public function validate(
        ExecutionContextInterface $context,
        string $datasetId,
        array $data,
        string $operation = 'CREATE',
    ): ValidationResultInterface {
        $rules = $this->kernel->rulesForDataset($context->applicationId(), $datasetId);
        $dataset = $this->kernel->dataset($context->applicationId(), $datasetId);

        // Normalisasi kunci payload ke UPPER_CASE agar cocok dengan COLUMN_NAME.
        $normalized = [];
        foreach ($data as $k => $v) {
            $normalized[strtoupper((string) $k)] = $v;
        }

        $errors = [];
        foreach ($rules as $rule) {
            $column = $rule['column'] !== null ? strtoupper((string) $rule['column']) : null;
            $ruleType = (string) $rule['ruleType'];
            $value = $column !== null ? ($normalized[$column] ?? null) : null;

            $message = $this->evaluate($ruleType, $rule, $value, $normalized, $dataset, $datasetId, $operation, $context);
            if ($message !== null) {
                $key = $column ?? '_record';
                $errors[$key][] = $message;
            }
        }

        return new ValidationResult($errors);
    }

    /**
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>|null  $dataset
     */
    private function evaluate(
        string $ruleType,
        array $rule,
        mixed $value,
        array $data,
        ?array $dataset,
        string $datasetId,
        string $operation,
        ExecutionContextInterface $context,
    ): ?string {
        $expr = $rule['expression'];
        $ok = match ($ruleType) {
            'REQUIRED' => ! $this->isEmpty($value),
            'MIN_LENGTH' => $this->isEmpty($value) || mb_strlen((string) $value) >= (int) $this->scalar($expr),
            'MAX_LENGTH' => $this->isEmpty($value) || mb_strlen((string) $value) <= (int) $this->scalar($expr),
            'MIN_VALUE' => $this->isEmpty($value) || (float) $value >= (float) $this->scalar($expr),
            'MAX_VALUE' => $this->isEmpty($value) || (float) $value <= (float) $this->scalar($expr),
            'REGEX' => $this->isEmpty($value) || $this->matchRegex((string) $this->scalar($expr), (string) $value),
            'UNIQUE' => $this->isEmpty($value) || $this->isUnique($dataset, $rule, $value, $data, $operation),
            'FOREIGN_KEY' => $this->isEmpty($value) || $this->foreignKeyExists($rule, $value),
            default => true, // FORMULA/CONDITIONAL menyusul di F2
        };

        if ($ok) {
            return null;
        }

        return (string) ($rule['message'] ?? $this->defaultMessage($ruleType, $rule));
    }

    private function isEmpty(mixed $value): bool
    {
        return $value === null || $value === '' || (is_array($value) && $value === []);
    }

    /**
     * Ambil nilai skalar dari ekspresi rule (angka/string atau JSON {"value":x}).
     */
    private function scalar(mixed $expr): mixed
    {
        if ($expr === null) {
            return null;
        }
        $str = trim((string) $expr);
        if ($str !== '' && ($str[0] === '{' || $str[0] === '[')) {
            $decoded = json_decode($str, true);
            if (is_array($decoded)) {
                return $decoded['value'] ?? ($decoded[0] ?? null);
            }
        }

        return $str;
    }

    private function matchRegex(string $pattern, string $value): bool
    {
        if ($pattern === '') {
            return true;
        }
        // Bungkus dengan delimiter bila belum ada.
        if (@preg_match($pattern, '') === false) {
            $pattern = '/'.str_replace('/', '\/', $pattern).'/';
        }

        return (bool) preg_match($pattern, $value);
    }

    /**
     * @param  array<string, mixed>|null  $dataset
     * @param  array<string, mixed>  $rule
     * @param  array<string, mixed>  $data
     */
    private function isUnique(?array $dataset, array $rule, mixed $value, array $data, string $operation): bool
    {
        if ($dataset === null || ($rule['column'] ?? null) === null) {
            return true;
        }
        $type = (string) ($dataset['sourceType'] ?? '');
        if (! in_array($type, ['TABLE', 'VIEW'], true)) {
            return true;
        }
        $table = strtoupper((string) $dataset['sourceObject']);
        $column = strtoupper((string) $rule['column']);
        $pk = strtoupper((string) ($dataset['primaryKey'] ?? ''));

        if (! preg_match('/^[A-Z0-9_$#]+$/', $table) || ! preg_match('/^[A-Z0-9_$#]+$/', $column)) {
            return true;
        }

        $sql = "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?";
        $bindings = [$value];

        // Pada UPDATE, kecualikan baris saat ini bila key tersedia.
        $currentKey = $data[$pk] ?? null;
        if ($operation === 'UPDATE' && $currentKey !== null && $pk !== '') {
            $sql .= " AND {$pk} <> HEXTORAW(?)";
            $bindings[] = strtoupper((string) $currentKey);
        }

        $count = (int) $this->connection->scalar($sql, $bindings);

        return $count === 0;
    }

    /**
     * FOREIGN_KEY expression: JSON {"table":"...","column":"..."}.
     *
     * @param  array<string, mixed>  $rule
     */
    private function foreignKeyExists(array $rule, mixed $value): bool
    {
        $expr = $rule['expression'];
        if ($expr === null) {
            return true;
        }
        $cfg = json_decode((string) $expr, true);
        if (! is_array($cfg) || ! isset($cfg['table'], $cfg['column'])) {
            return true;
        }
        $table = strtoupper((string) $cfg['table']);
        $column = strtoupper((string) $cfg['column']);
        if (! preg_match('/^[A-Z0-9_$#]+$/', $table) || ! preg_match('/^[A-Z0-9_$#]+$/', $column)) {
            return true;
        }

        $count = (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM {$table} WHERE {$column} = ?",
            [$value],
        );

        return $count > 0;
    }

    /**
     * @param  array<string, mixed>  $rule
     */
    private function defaultMessage(string $ruleType, array $rule): string
    {
        $field = (string) ($rule['column'] ?? 'Field');

        return match ($ruleType) {
            'REQUIRED' => "{$field} wajib diisi.",
            'UNIQUE' => "{$field} sudah digunakan.",
            'MIN_LENGTH', 'MAX_LENGTH' => "Panjang {$field} tidak valid.",
            'MIN_VALUE', 'MAX_VALUE' => "Nilai {$field} di luar rentang.",
            'REGEX' => "Format {$field} tidak valid.",
            'FOREIGN_KEY' => "Referensi {$field} tidak ditemukan.",
            default => "{$field} tidak valid.",
        };
    }
}
