<?php

declare(strict_types=1);

namespace Odaf\Engine\Audit;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Audit\Contracts\AuditEngineInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use Throwable;

/**
 * BB-08 Audit Engine — implementasi Oracle.
 *
 * Menulis peristiwa ke AUD_EVENT (append-only, immutable). Kegagalan audit tidak
 * boleh menggagalkan operasi bisnis, namun tetap dicatat ke log aplikasi.
 */
final class OracleAuditEngine implements AuditEngineInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    public function record(
        ExecutionContextInterface $context,
        string $eventType,
        string $objectId,
        array $before = [],
        array $after = [],
    ): void {
        try {
            $this->connection->insert(
                'INSERT INTO AUD_EVENT
                    (EVENT_ID, EVENT_TYPE, OBJECT_ID, DATASET_CODE, USER_ID, APPLICATION_ID, BEFORE_DATA, AFTER_DATA)
                 VALUES (HEXTORAW(?), ?, HEXTORAW(?), ?, HEXTORAW(?), HEXTORAW(?), ?, ?)',
                [
                    strtoupper($this->identity->generate()),
                    $eventType,
                    $objectId !== '' ? $this->rawOrNull($objectId) : null,
                    $context->attributes()['datasetCode'] ?? null,
                    $context->userId() !== null ? $this->rawOrNull($context->userId()) : null,
                    $this->rawOrNull($context->applicationId()),
                    $before === [] ? null : $this->encode($before),
                    $after === [] ? null : $this->encode($after),
                ],
            );
        } catch (Throwable $e) {
            // Audit tidak boleh menggagalkan transaksi bisnis.
            logger()->error('ODAF audit gagal', [
                'eventType' => $eventType,
                'objectId' => $objectId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * OBJECT_ID di AUD_EVENT adalah RAW(16). Bila nilai bukan hex 32-char
     * (mis. business key), simpan null pada kolom RAW dan biarkan konteks lain
     * (DATASET_CODE) mengidentifikasi baris.
     */
    private function rawOrNull(string $value): ?string
    {
        $hex = strtoupper($value);

        return preg_match('/^[0-9A-F]{32}$/', $hex) === 1 ? $hex : null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encode(array $data): string
    {
        return (string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
