<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification\Contracts;

use Odaf\Engine\Notification\NotificationMessage;

/**
 * Kontrak channel pengiriman notifikasi (Vol.3 Bab 15 §9).
 *
 * Notification Engine tidak bergantung pada implementasi channel; setiap channel
 * (In-App, Email, SMS, Webhook, dst) mengimplementasikan kontrak ini.
 */
interface NotificationChannelInterface
{
    /** Kode channel, mis. IN_APP, LOG, EMAIL. */
    public function code(): string;

    /**
     * Kirim pesan. Mengembalikan status pengiriman: SENT | FAILED.
     */
    public function deliver(NotificationMessage $message): string;
}
