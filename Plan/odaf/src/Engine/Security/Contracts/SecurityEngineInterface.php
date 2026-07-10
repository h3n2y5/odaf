<?php

declare(strict_types=1);

namespace Odaf\Engine\Security\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-07 Security Engine (Vol.3 Bab 18).
 *
 * Otorisasi terpusat & metadata-driven. Authorization SHALL mendahului setiap
 * operasi bisnis (Vol.1 Bab 09 §12). Mendukung permission pada level menu, form,
 * field, button, object, dan row-level.
 */
interface SecurityEngineInterface
{
    /** Tingkat akses berbutir-halus (SEC_ACCESS). */
    public const LEVEL_FULL = 'FULL';

    public const LEVEL_READONLY = 'READONLY';

    public const LEVEL_MASKED = 'MASKED';

    public const LEVEL_NONE = 'NONE';

    /** Tipe objek yang dilindungi SEC_ACCESS. */
    public const OBJ_MENU = 'MENU';

    public const OBJ_PAGE = 'PAGE';

    public const OBJ_FIELD = 'FIELD';

    /**
     * Apakah context memiliki permission tertentu atas sebuah objek.
     *
     * @param  string  $action  mis. "READ", "CREATE", "UPDATE", "DELETE", "EXECUTE"
     */
    public function can(ExecutionContextInterface $context, string $objectId, string $action): bool;

    /**
     * Tingkat akses efektif context atas sebuah objek UI (MENU|PAGE|FIELD).
     * Mengembalikan salah satu LEVEL_*. Objek tanpa aturan SEC_ACCESS bersifat
     * terbuka (FULL); superuser selalu FULL.
     *
     * @param  string  $objectType  salah satu OBJ_*
     */
    public function accessLevel(ExecutionContextInterface $context, string $objectType, string $objectId): string;

    /**
     * Peta tingkat akses efektif untuk banyak objek sekaligus (batch).
     *
     * @param  string  $objectType  salah satu OBJ_*
     * @param  array<int, string>  $objectIds
     * @return array<string, string> kunci = OBJECT_ID (uppercase), nilai = LEVEL_*
     */
    public function accessLevels(ExecutionContextInterface $context, string $objectType, array $objectIds): array;

    /**
     * Tegakkan permission; lempar exception bila tidak diizinkan.
     *
     * @throws AuthorizationException
     */
    public function authorize(ExecutionContextInterface $context, string $objectId, string $action): void;

    /**
     * Apakah context adalah superuser (memiliki role ADMIN) — akses penuh.
     * Dipakai a.l. untuk membuka ODAF Studio (tooling admin).
     */
    public function isSuperuser(ExecutionContextInterface $context): bool;

    /**
     * Daftar field yang boleh dilihat context pada sebuah form/dataset
     * (field-level security).
     *
     * @param  array<int, string>  $fieldIds
     * @return array<int, string> subset field yang diizinkan
     */
    public function visibleFields(ExecutionContextInterface $context, array $fieldIds): array;

    /**
     * Predikat row-level security untuk diselipkan ke query dataset (bila ada).
     *
     * @return array<string, mixed>
     */
    public function rowFilter(ExecutionContextInterface $context, string $datasetId): array;
}
