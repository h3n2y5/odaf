<?php

declare(strict_types=1);

namespace Odaf\Metadata;

/**
 * Graph metadata lengkap sebuah aplikasi.
 *
 * Aggregate yang dibutuhkan compiler (BB-02): aplikasi beserta seluruh modul,
 * menu, halaman, field, dataset, LOV, aturan validasi, dan permission. Semua
 * koleksi anak disimpan sebagai array baris mentah (kunci kolom UPPER_CASE) agar
 * compiler dapat mentransformasinya menjadi MIR tanpa query tambahan.
 *
 * Merupakan MetadataObjectInterface berdomain "APP" (aggregate root aplikasi).
 *
 * Referensi: Volume 1 Bab 10, Volume 2 Bab 12-16.
 */
final class ApplicationGraph extends MetadataObject
{
    /**
     * @param  array<string, mixed>  $application  baris APP_APPLICATION
     * @param  array<int, array<string,mixed>>  $modules  baris APP_MODULE
     * @param  array<int, array<string,mixed>>  $menus  baris APP_MENU
     * @param  array<int, array<string,mixed>>  $pages  baris UI_PAGE
     * @param  array<int, array<string,mixed>>  $fields  baris UI_FIELD
     * @param  array<int, array<string,mixed>>  $datasets  baris DS_DATASET
     * @param  array<int, array<string,mixed>>  $lovs  baris DS_LOV
     * @param  array<int, array<string,mixed>>  $rules  baris VAL_RULE
     * @param  array<int, array<string,mixed>>  $permissions  baris SEC_PERMISSION
     * @param  array<int, array<string,mixed>>  $workflows  baris WF_WORKFLOW
     * @param  array<int, array<string,mixed>>  $activities  baris WF_ACTIVITY
     * @param  array<int, array<string,mixed>>  $transitions  baris WF_TRANSITION
     * @param  array<int, array<string,mixed>>  $notifications  baris NTF_NOTIFICATION
     * @param  array<int, array<string,mixed>>  $subscriptions  baris NTF_SUBSCRIPTION
     */
    public function __construct(
        array $application,
        private readonly array $modules = [],
        private readonly array $menus = [],
        private readonly array $pages = [],
        private readonly array $fields = [],
        private readonly array $datasets = [],
        private readonly array $lovs = [],
        private readonly array $rules = [],
        private readonly array $permissions = [],
        private readonly array $workflows = [],
        private readonly array $activities = [],
        private readonly array $transitions = [],
        private readonly array $notifications = [],
        private readonly array $subscriptions = [],
    ) {
        parent::__construct(
            objectId: (string) ($application['OBJECT_ID'] ?? ''),
            objectCode: (string) ($application['OBJECT_CODE'] ?? ''),
            objectName: (string) ($application['OBJECT_NAME'] ?? ''),
            domain: 'APP',
            versionNo: (int) ($application['VERSION_NO'] ?? 1),
            status: (string) ($application['STATUS'] ?? 'DRAFT'),
            attributes: $application,
        );
    }

    /** @return array<int, array<string,mixed>> */
    public function modules(): array
    {
        return $this->modules;
    }

    /** @return array<int, array<string,mixed>> */
    public function menus(): array
    {
        return $this->menus;
    }

    /** @return array<int, array<string,mixed>> */
    public function pages(): array
    {
        return $this->pages;
    }

    /**
     * Field untuk sebuah halaman tertentu (OBJECT_ID halaman).
     *
     * @return array<int, array<string,mixed>>
     */
    public function fieldsForPage(string $pageId): array
    {
        return array_values(array_filter(
            $this->fields,
            static fn (array $f): bool => (string) ($f['PAGE_ID'] ?? '') === $pageId,
        ));
    }

    /** @return array<int, array<string,mixed>> */
    public function fields(): array
    {
        return $this->fields;
    }

    /** @return array<int, array<string,mixed>> */
    public function datasets(): array
    {
        return $this->datasets;
    }

    /** @return array<int, array<string,mixed>> */
    public function lovs(): array
    {
        return $this->lovs;
    }

    /** @return array<int, array<string,mixed>> */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * Aturan validasi untuk sebuah dataset tertentu.
     *
     * @return array<int, array<string,mixed>>
     */
    public function rulesForDataset(string $datasetId): array
    {
        return array_values(array_filter(
            $this->rules,
            static fn (array $r): bool => (string) ($r['DATASET_ID'] ?? '') === $datasetId,
        ));
    }

    /** @return array<int, array<string,mixed>> */
    public function permissions(): array
    {
        return $this->permissions;
    }

    /** @return array<int, array<string,mixed>> */
    public function workflows(): array
    {
        return $this->workflows;
    }

    /** @return array<int, array<string,mixed>> */
    public function activities(): array
    {
        return $this->activities;
    }

    /** @return array<int, array<string,mixed>> */
    public function transitions(): array
    {
        return $this->transitions;
    }

    /** @return array<int, array<string,mixed>> */
    public function notifications(): array
    {
        return $this->notifications;
    }

    /** @return array<int, array<string,mixed>> */
    public function subscriptions(): array
    {
        return $this->subscriptions;
    }

    /**
     * Activity untuk sebuah workflow tertentu (OBJECT_ID workflow).
     *
     * @return array<int, array<string,mixed>>
     */
    public function activitiesForWorkflow(string $workflowId): array
    {
        return array_values(array_filter(
            $this->activities,
            static fn (array $a): bool => (string) ($a['WORKFLOW_ID'] ?? '') === $workflowId,
        ));
    }

    /**
     * Transition untuk sebuah workflow tertentu (OBJECT_ID workflow).
     *
     * @return array<int, array<string,mixed>>
     */
    public function transitionsForWorkflow(string $workflowId): array
    {
        return array_values(array_filter(
            $this->transitions,
            static fn (array $t): bool => (string) ($t['WORKFLOW_ID'] ?? '') === $workflowId,
        ));
    }

    /**
     * Cari satu dataset berdasarkan OBJECT_ID.
     *
     * @return array<string,mixed>|null
     */
    public function dataset(string $datasetId): ?array
    {
        foreach ($this->datasets as $ds) {
            if ((string) ($ds['OBJECT_ID'] ?? '') === $datasetId) {
                return $ds;
            }
        }

        return null;
    }

    /**
     * Cari satu halaman berdasarkan OBJECT_ID.
     *
     * @return array<string,mixed>|null
     */
    public function page(string $pageId): ?array
    {
        foreach ($this->pages as $p) {
            if ((string) ($p['OBJECT_ID'] ?? '') === $pageId) {
                return $p;
            }
        }

        return null;
    }
}
