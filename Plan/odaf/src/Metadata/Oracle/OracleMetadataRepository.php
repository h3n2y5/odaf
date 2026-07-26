<?php

declare(strict_types=1);

namespace Odaf\Metadata\Oracle;

use Illuminate\Database\ConnectionInterface;
use InvalidArgumentException;
use Odaf\Metadata\ApplicationGraph;
use Odaf\Metadata\Contracts\MetadataObjectInterface;
use Odaf\Metadata\Contracts\MetadataRepositoryInterface;
use Odaf\Metadata\MetadataObject;
use RuntimeException;

/**
 * BB-01 Metadata Repository — implementasi Oracle.
 *
 * Membaca metadata design-time dari tabel APP_*, UI_*, DS_*, VAL_*, SEC_*.
 * Layer ini TIDAK dikonsumsi runtime kernel (CORE-002); ia hanya melayani
 * compiler dan tooling design-time.
 *
 * Konvensi: seluruh kolom RAW(16) dibaca sebagai hex uppercase via RAWTOHEX dan
 * ditulis via HEXTORAW agar identitas konsisten lintas driver.
 */
final class OracleMetadataRepository implements MetadataRepositoryInterface
{
    /**
     * Peta domain -> tabel aggregate root design-time.
     *
     * @var array<string, string>
     */
    private const DOMAIN_TABLE = [
        'APP' => 'APP_APPLICATION',
        'MODULE' => 'APP_MODULE',
        'MENU' => 'APP_MENU',
        'UI' => 'UI_PAGE',
        'FIELD' => 'UI_FIELD',
        'DS' => 'DS_DATASET',
        'LOV' => 'DS_LOV',
        'VAL' => 'VAL_RULE',
        'SEC' => 'SEC_USER',
    ];

    public function __construct(private readonly ConnectionInterface $connection) {}

    public function findById(string $objectId): ?MetadataObjectInterface
    {
        // Domain tak diketahui dari id saja; telusuri tabel aggregate root.
        foreach (self::DOMAIN_TABLE as $domain => $table) {
            $row = $this->fetchRow(
                "SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID FROM {$table} t WHERE t.OBJECT_ID = HEXTORAW(?)",
                [strtoupper($objectId)],
            );
            if ($row !== null) {
                return MetadataObject::fromRow($domain, $row);
            }
        }

        return null;
    }

    public function findByCode(string $domain, string $objectCode): ?MetadataObjectInterface
    {
        $table = $this->tableFor($domain);

        $row = $this->fetchRow(
            "SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID FROM {$table} t WHERE t.OBJECT_CODE = ?",
            [$objectCode],
        );

        return $row === null ? null : MetadataObject::fromRow($domain, $row);
    }

    public function allInDomain(string $domain): iterable
    {
        $table = $this->tableFor($domain);

        $rows = $this->connection->select(
            "SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID FROM {$table} t ORDER BY t.OBJECT_CODE",
        );

        foreach ($rows as $row) {
            yield MetadataObject::fromRow($domain, $this->normalize($row));
        }
    }

    public function loadApplicationGraph(string $applicationId): MetadataObjectInterface
    {
        $appId = strtoupper($applicationId);

        $application = $this->fetchRow(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.CREATED_BY) AS CREATED_BY,
                    RAWTOHEX(t.UPDATED_BY) AS UPDATED_BY
             FROM APP_APPLICATION t WHERE t.OBJECT_ID = HEXTORAW(?)',
            [$appId],
        );

        if ($application === null) {
            throw new RuntimeException("Aplikasi metadata tidak ditemukan: {$applicationId}");
        }

        $modules = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.APPLICATION_ID) AS APPLICATION_ID
             FROM APP_MODULE t WHERE t.APPLICATION_ID = HEXTORAW(?) ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
            [$appId],
        );

        $menus = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.MODULE_ID) AS MODULE_ID,
                    RAWTOHEX(t.PARENT_MENU_ID) AS PARENT_MENU_ID, RAWTOHEX(t.PAGE_ID) AS PAGE_ID
             FROM APP_MENU t
             WHERE t.MODULE_ID IN (SELECT OBJECT_ID FROM APP_MODULE WHERE APPLICATION_ID = HEXTORAW(?))
             ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
            [$appId],
        );

        $pages = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.APPLICATION_ID) AS APPLICATION_ID,
                    RAWTOHEX(t.DATASET_ID) AS DATASET_ID
             FROM UI_PAGE t WHERE t.APPLICATION_ID = HEXTORAW(?) ORDER BY t.OBJECT_CODE',
            [$appId],
        );

        $fields = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.PAGE_ID) AS PAGE_ID,
                    RAWTOHEX(t.LOV_ID) AS LOV_ID
             FROM UI_FIELD t
             WHERE t.PAGE_ID IN (SELECT OBJECT_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?))
             ORDER BY t.PAGE_ID, t.DISPLAY_ORDER, t.OBJECT_CODE',
            [$appId],
        );

        // Dataset yang dipakai halaman aplikasi ini.
        $datasets = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID
             FROM DS_DATASET t
             WHERE t.OBJECT_ID IN (
                 SELECT DATASET_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL
             )
             ORDER BY t.OBJECT_CODE',
            [$appId],
        );

        $datasetIds = array_map(static fn (array $d): string => (string) $d['OBJECT_ID'], $datasets);

        $lovs = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID
             FROM DS_LOV t
             WHERE t.OBJECT_ID IN (
                 SELECT LOV_ID FROM UI_FIELD WHERE LOV_ID IS NOT NULL AND PAGE_ID IN (
                     SELECT OBJECT_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?)
                 )
             )
             ORDER BY t.OBJECT_CODE',
            [$appId],
        );

        $rules = [];
        if ($datasetIds !== []) {
            $rules = $this->fetchAll(
                'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.DATASET_ID) AS DATASET_ID,
                        RAWTOHEX(t.FIELD_ID) AS FIELD_ID
                 FROM VAL_RULE t
                 WHERE t.DATASET_ID IN (
                     SELECT DATASET_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL
                 ) AND t.ACTIVE_FLAG = 1
                 ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
                [$appId],
            );
        }

        // Workflow yang mengatur dataset aplikasi ini (F2).
        $workflows = [];
        $activities = [];
        $transitions = [];
        if ($datasetIds !== []) {
            $workflows = $this->fetchAll(
                'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.DATASET_ID) AS DATASET_ID
                 FROM WF_WORKFLOW t
                 WHERE t.DATASET_ID IN (
                     SELECT DATASET_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL
                 ) AND t.STATUS = ?
                 ORDER BY t.OBJECT_CODE',
                [$appId, 'PUBLISHED'],
            );

            if ($workflows !== []) {
                $activities = $this->fetchAll(
                    'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.WORKFLOW_ID) AS WORKFLOW_ID
                     FROM WF_ACTIVITY t
                     WHERE t.WORKFLOW_ID IN (
                         SELECT OBJECT_ID FROM WF_WORKFLOW WHERE DATASET_ID IN (
                             SELECT DATASET_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL
                         )
                     )
                     ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
                    [$appId],
                );

                $transitions = $this->fetchAll(
                    'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.WORKFLOW_ID) AS WORKFLOW_ID,
                            RAWTOHEX(t.FROM_ACTIVITY_ID) AS FROM_ACTIVITY_ID, RAWTOHEX(t.TO_ACTIVITY_ID) AS TO_ACTIVITY_ID,
                            RAWTOHEX(t.REQUIRED_ROLE_ID) AS REQUIRED_ROLE_ID
                     FROM WF_TRANSITION t
                     WHERE t.WORKFLOW_ID IN (
                         SELECT OBJECT_ID FROM WF_WORKFLOW WHERE DATASET_ID IN (
                             SELECT DATASET_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?) AND DATASET_ID IS NOT NULL
                         )
                     )
                     ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
                    [$appId],
                );
            }
        }

        // Notifikasi & subscription (F2). Untuk F2.2 dimuat global (published/aktif).
        $notifications = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID FROM NTF_NOTIFICATION t WHERE t.STATUS = ? ORDER BY t.OBJECT_CODE',
            ['PUBLISHED'],
        );
        $subscriptions = [];
        if ($notifications !== []) {
            $subscriptions = $this->fetchAll(
                'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.NOTIFICATION_ID) AS NOTIFICATION_ID
                 FROM NTF_SUBSCRIPTION t
                 WHERE t.STATUS = ? AND t.ACTIVE_FLAG = 1
                 ORDER BY t.DISPLAY_ORDER, t.OBJECT_CODE',
                ['PUBLISHED'],
            );
        }

        // QR Code configs per page (F2 extension).
        $qrConfigs = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.PAGE_ID) AS PAGE_ID
             FROM QR_CONFIG t
             WHERE t.PAGE_ID IN (
                 SELECT OBJECT_ID FROM UI_PAGE WHERE APPLICATION_ID = HEXTORAW(?)
             ) AND t.STATUS = ?
             ORDER BY t.OBJECT_CODE',
            [$appId, 'PUBLISHED'],
        );
        $rptTemplates = $this->fetchAll(
            'SELECT t.*, RAWTOHEX(t.OBJECT_ID) AS OBJECT_ID, RAWTOHEX(t.PAGE_ID) AS PAGE_ID
             FROM RPT_TEMPLATE t
             WHERE t.APPLICATION_ID = HEXTORAW(?) AND t.STATUS = ?',
            [$appId, 'PUBLISHED'],
        );

        return new ApplicationGraph(
            application: $application,
            modules: $modules,
            menus: $menus,
            pages: $pages,
            fields: $fields,
            datasets: $datasets,
            lovs: $lovs,
            rules: $rules,
            permissions: [],
            workflows: $workflows,
            activities: $activities,
            transitions: $transitions,
            notifications: $notifications,
            subscriptions: $subscriptions,
            qrConfigs: $qrConfigs,
            rptTemplates: $rptTemplates,
        );
    }

    public function save(MetadataObjectInterface $object): void
    {
        // Penulisan metadata design-time (create/update aggregate root) akan
        // ditangani ODAF Studio (BB-15) pada fase F3. Untuk F1, repository
        // bersifat read-only demi menjaga cakupan vertical slice.
        throw new RuntimeException('save() metadata belum didukung pada F1 (read-only).');
    }

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<string, mixed>|null
     */
    private function fetchRow(string $sql, array $bindings): ?array
    {
        $row = $this->connection->selectOne($sql, $bindings);

        return $row === null ? null : $this->normalize($row);
    }

    /**
     * @param  array<int, mixed>  $bindings
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql, array $bindings): array
    {
        return array_map(
            fn ($row): array => $this->normalize($row),
            $this->connection->select($sql, $bindings),
        );
    }

    /**
     * Normalisasi baris ke array asosiatif berkunci UPPER_CASE.
     *
     * @return array<string, mixed>
     */
    private function normalize(mixed $row): array
    {
        $arr = (array) $row;
        $out = [];
        foreach ($arr as $key => $value) {
            // Kolom CLOB (SOURCE_QUERY, DESCRIPTION, dll) dikembalikan oci8 sebagai
            // resource stream; baca menjadi string agar aman dikonsumsi compiler.
            if (is_resource($value)) {
                $value = stream_get_contents($value);
            }
            $out[strtoupper((string) $key)] = $value;
        }

        return $out;
    }

    private function tableFor(string $domain): string
    {
        $table = self::DOMAIN_TABLE[strtoupper($domain)] ?? null;
        if ($table === null) {
            throw new InvalidArgumentException("Domain metadata tidak dikenal: {$domain}");
        }

        return $table;
    }
}
