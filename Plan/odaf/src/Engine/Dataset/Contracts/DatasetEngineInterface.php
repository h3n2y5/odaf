<?php

declare(strict_types=1);

namespace Odaf\Engine\Dataset\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-05 Dataset Engine (Vol.3 Bab 11).
 *
 * Satu-satunya mesin CRUD generik platform. TIDAK ada CustomerRepository dsb —
 * seluruh entity diproses engine yang sama berdasarkan metadata dataset (DS_*).
 *
 * Tanggung jawab: CRUD, filter, sort, pagination, optimistic locking, koordinasi
 * transaksi, kolom audit & soft delete otomatis.
 */
interface DatasetEngineInterface
{
    /**
     * Baca banyak baris dengan filter/sort/paging.
     *
     * @param  array<string, mixed>  $criteria
     */
    public function query(
        ExecutionContextInterface $context,
        string $datasetId,
        array $criteria = [],
        int $page = 1,
        int $pageSize = 25,
    ): DatasetResultInterface;

    /**
     * Baca satu baris berdasarkan primary key bisnis.
     *
     * @return array<string, mixed>|null
     */
    public function find(ExecutionContextInterface $context, string $datasetId, string $key): ?array;

    /**
     * Buat baris baru. Mengembalikan primary key baris tersimpan.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(ExecutionContextInterface $context, string $datasetId, array $data): string;

    /**
     * Perbarui baris (optimistic locking via VERSION_NO).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ExecutionContextInterface $context, string $datasetId, string $key, array $data): void;

    /**
     * Hapus baris (soft/hard delete ditentukan metadata dataset).
     */
    public function delete(ExecutionContextInterface $context, string $datasetId, string $key): void;

    /**
     * Duplikat sebuah baris (clone) sebagai record baru.
     * PK baru digenerate; STATUS diset 'DRAFT' bila kolom ada; kolom audit
     * di-reset (CREATED_* = user & waktu sekarang). Mengembalikan key baru.
     */
    public function cloneRow(ExecutionContextInterface $context, string $datasetId, string $key): string;
}
