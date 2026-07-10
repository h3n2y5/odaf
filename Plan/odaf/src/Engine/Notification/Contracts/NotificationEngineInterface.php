<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification\Contracts;

use Odaf\Runtime\Contracts\ExecutionContextInterface;

/**
 * BB-10 Notification Engine (Vol.3 Bab 15).
 *
 * Mengeksekusi subscription notifikasi terkompilasi untuk sebuah event bisnis:
 * resolve subscription -> resolve penerima -> render template -> kirim via
 * channel -> catat & audit. Proses bisnis mem-publish event, bukan memanggil
 * mekanisme pengiriman langsung (NTF-002).
 */
interface NotificationEngineInterface
{
    /**
     * Publikasikan sebuah event bisnis; kirim seluruh notifikasi yang berlangganan.
     *
     * @param  array<string, mixed>  $payload  variabel untuk substitusi template
     *                                         + kunci khusus: entityKey, ownerId (untuk penerima OWNER).
     * @return int jumlah pesan terkirim
     */
    public function publish(ExecutionContextInterface $context, string $eventCode, array $payload = []): int;

    /**
     * Kotak masuk in-app untuk pengguna aktif (terbaru dahulu).
     *
     * @return array<int, array<string, mixed>>
     */
    public function inbox(ExecutionContextInterface $context, int $limit = 10): array;

    /** Jumlah notifikasi in-app belum dibaca untuk pengguna aktif. */
    public function unreadCount(ExecutionContextInterface $context): int;

    /** Tandai satu notifikasi in-app sebagai dibaca. */
    public function markRead(ExecutionContextInterface $context, string $notificationId): void;

    /** Tandai seluruh notifikasi in-app pengguna aktif sebagai dibaca. */
    public function markAllRead(ExecutionContextInterface $context): void;
}
