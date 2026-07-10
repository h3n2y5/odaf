<?php

declare(strict_types=1);

namespace Odaf\Metadata\Contracts;

/**
 * BB-01 Metadata Repository.
 *
 * Sumber otoritatif seluruh metadata design-time (APP_*, UI_*, DS_*, VAL_*, SEC_*).
 * Layer ini HANYA membaca/menulis metadata yang dapat diedit; ia TIDAK
 * dikonsumsi langsung oleh runtime (lihat CORE-002 — runtime hanya mengeksekusi
 * artefak terkompilasi).
 *
 * Referensi: Volume 1 Bab 10 (Building Block View), Volume 2.
 */
interface MetadataRepositoryInterface
{
    /**
     * Ambil satu aggregate root metadata berdasarkan OBJECT_ID (RAW(16)).
     */
    public function findById(string $objectId): ?MetadataObjectInterface;

    /**
     * Ambil aggregate root berdasarkan OBJECT_CODE dalam domain tertentu.
     * OBJECT_CODE bersifat unik per domain dan immutable (Vol.2 Bab 08).
     */
    public function findByCode(string $domain, string $objectCode): ?MetadataObjectInterface;

    /**
     * Ambil seluruh objek metadata pada satu domain (mis. "APP", "UI", "DS").
     *
     * @return iterable<MetadataObjectInterface>
     */
    public function allInDomain(string $domain): iterable;

    /**
     * Ambil definisi lengkap sebuah aplikasi beserta modul, menu, halaman,
     * field, dataset, validasi, dan security yang dibutuhkan compiler.
     */
    public function loadApplicationGraph(string $applicationId): MetadataObjectInterface;

    /**
     * Simpan/replace sebuah aggregate root metadata (design-time).
     */
    public function save(MetadataObjectInterface $object): void;
}
