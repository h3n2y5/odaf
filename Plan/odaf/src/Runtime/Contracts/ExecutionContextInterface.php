<?php

declare(strict_types=1);

namespace Odaf\Runtime\Contracts;

/**
 * Execution Context (Vol.3 Bab 10).
 *
 * Membawa identitas pengguna, tenant/aplikasi aktif, locale, dan atribut request
 * yang dibutuhkan seluruh engine selama satu siklus eksekusi. Bersifat immutable.
 */
interface ExecutionContextInterface
{
    /** OBJECT_ID user aktif (SEC_USER). */
    public function userId(): ?string;

    /** OBJECT_ID aplikasi aktif (APP_APPLICATION). */
    public function applicationId(): string;

    /** Locale aktif untuk i18n caption (mis. "id", "en"). */
    public function locale(): string;

    /**
     * OBJECT_ID role yang dimiliki user aktif.
     *
     * @return array<int, string>
     */
    public function roleIds(): array;

    /**
     * Atribut request tambahan (parameter, correlation id, dsb).
     *
     * @return array<string, mixed>
     */
    public function attributes(): array;
}
