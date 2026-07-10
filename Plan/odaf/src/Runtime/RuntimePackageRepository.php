<?php

declare(strict_types=1);

namespace Odaf\Runtime;

use Illuminate\Database\ConnectionInterface;
use Odaf\Compiler\Contracts\RuntimePackageInterface;
use Odaf\Compiler\MetadataCompiler;
use Odaf\Compiler\RuntimePackage;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use RuntimeException;

/**
 * Persistensi Runtime Package pada Runtime Repository (RT_PACKAGE).
 *
 * Menjaga isolasi design-time vs runtime (CORE-004): runtime kernel hanya memuat
 * package dari sini, tidak pernah dari tabel metadata design-time.
 */
final class RuntimePackageRepository
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    /**
     * Simpan package terkompilasi. Idempoten terhadap (APPLICATION_ID, VERSION):
     * bila versi identik sudah ada, tidak menyimpan ulang.
     */
    public function store(RuntimePackageInterface $package, ?string $compiledBy = null): void
    {
        $exists = $this->connection->selectOne(
            'SELECT 1 AS X FROM RT_PACKAGE WHERE APPLICATION_ID = HEXTORAW(?) AND PACKAGE_VERSION = ?',
            [strtoupper($package->applicationId()), $package->packageVersion()],
        );

        if ($exists !== null) {
            return;
        }

        $payloadJson = $package instanceof RuntimePackage
            ? $package->toCanonicalJson()
            : RuntimePackage::canonicalJson($package->toArray());

        $this->connection->insert(
            'INSERT INTO RT_PACKAGE
                (OBJECT_ID, APPLICATION_ID, PACKAGE_VERSION, CHECKSUM, COMPILER_VERSION, PAYLOAD, ACTIVE_FLAG, CREATED_BY)
             VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, ?, ?, 0, ?)',
            [
                strtoupper($package->packageId()),
                strtoupper($package->applicationId()),
                $package->packageVersion(),
                $package->checksum(),
                MetadataCompiler::COMPILER_VERSION,
                $payloadJson,
                $compiledBy !== null ? strtoupper($compiledBy) : null,
            ],
        );
    }

    /**
     * Aktifkan sebuah versi package (menonaktifkan versi lain aplikasi yang sama).
     */
    public function activate(string $applicationId, string $packageVersion): void
    {
        $appId = strtoupper($applicationId);

        $this->connection->transaction(function () use ($appId, $packageVersion): void {
            $this->connection->update(
                'UPDATE RT_PACKAGE SET ACTIVE_FLAG = 0 WHERE APPLICATION_ID = HEXTORAW(?)',
                [$appId],
            );
            $affected = $this->connection->update(
                'UPDATE RT_PACKAGE SET ACTIVE_FLAG = 1, ACTIVATED_AT = SYSTIMESTAMP
                 WHERE APPLICATION_ID = HEXTORAW(?) AND PACKAGE_VERSION = ?',
                [$appId, $packageVersion],
            );
            if ($affected === 0) {
                throw new RuntimeException("Package versi {$packageVersion} tidak ditemukan untuk diaktifkan.");
            }
        });
    }

    /**
     * Muat package aktif untuk sebuah aplikasi, atau null bila belum ada.
     */
    public function loadActive(string $applicationId): ?RuntimePackageInterface
    {
        $row = $this->connection->selectOne(
            'SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, RAWTOHEX(APPLICATION_ID) AS APPLICATION_ID,
                    PACKAGE_VERSION, CHECKSUM, PAYLOAD
             FROM RT_PACKAGE
             WHERE APPLICATION_ID = HEXTORAW(?) AND ACTIVE_FLAG = 1',
            [strtoupper($applicationId)],
        );

        return $row === null ? null : $this->hydrate((array) $row);
    }

    /**
     * Muat package aktif berdasarkan OBJECT_CODE aplikasi (dari payload).
     *
     * Menjaga isolasi CORE-002: runtime tidak menyentuh tabel design-time untuk
     * meresolusi kode aplikasi; kode dibaca dari payload package yang tersimpan.
     */
    public function loadActiveByCode(string $applicationCode): ?RuntimePackageInterface
    {
        $rows = $this->connection->select(
            'SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, RAWTOHEX(APPLICATION_ID) AS APPLICATION_ID,
                    PACKAGE_VERSION, CHECKSUM, PAYLOAD
             FROM RT_PACKAGE WHERE ACTIVE_FLAG = 1',
        );

        foreach ($rows as $row) {
            $package = $this->hydrate((array) $row);
            $code = $package->toArray()['application']['code'] ?? null;
            if ($code === $applicationCode) {
                return $package;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function hydrate(array $row): RuntimePackageInterface
    {
        $row = array_change_key_case($row, CASE_UPPER);
        $payloadRaw = $row['PAYLOAD'];

        // CLOB dari oci8 dapat berupa resource/stream.
        if (is_resource($payloadRaw)) {
            $payloadRaw = stream_get_contents($payloadRaw);
        }

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $payloadRaw, true, flags: JSON_THROW_ON_ERROR);

        return new RuntimePackage(
            packageId: (string) $row['OBJECT_ID'],
            packageVersion: (string) $row['PACKAGE_VERSION'],
            applicationId: (string) $row['APPLICATION_ID'],
            checksum: (string) $row['CHECKSUM'],
            payload: $payload,
        );
    }
}
