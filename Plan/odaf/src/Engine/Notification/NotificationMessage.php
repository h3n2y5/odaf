<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification;

/**
 * Pesan notifikasi yang siap dikirim melalui sebuah channel (immutable).
 */
final class NotificationMessage
{
    public function __construct(
        public readonly string $subject,
        public readonly string $body,
        public readonly ?string $recipientUserId = null,
        public readonly ?string $recipientAddress = null,
        public readonly ?string $notificationId = null,
        public readonly ?string $subscriptionCode = null,
        public readonly ?string $eventCode = null,
        public readonly ?string $entityKey = null,
        public readonly ?string $actorId = null,
    ) {}
}
