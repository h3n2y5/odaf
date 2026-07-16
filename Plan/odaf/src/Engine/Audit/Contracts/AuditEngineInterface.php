<?php

declare(strict_types=1);

namespace Odaf\Engine\Audit\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-08 Audit Engine (Vol.1 Bab 10 §13).
 *
 * Merekam aktivitas bisnis. Rekaman audit bersifat immutable (append-only).
 */
interface AuditEngineInterface
{
    /**
     * Catat sebuah peristiwa audit.
     *
     * @param  array<string, mixed>  $before  state sebelum perubahan (opsional)
     * @param  array<string, mixed>  $after  state sesudah perubahan (opsional)
     */
    public function record(
        ExecutionContextInterface $context,
        string $eventType,
        string $objectId,
        ?string $objectName = null,
        array $before = [],
        array $after = [],
    ): void;
}
