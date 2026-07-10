<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification\Channel;

use Odaf\Engine\Notification\Contracts\NotificationChannelInterface;
use Odaf\Engine\Notification\NotificationMessage;

/**
 * Channel Log: menulis notifikasi ke log aplikasi.
 *
 * Berfungsi sebagai fallback dan sebagai stand-in untuk channel eksternal
 * (Email/SMS/Webhook) yang implementasi transport-nya menyusul.
 */
final class LogChannel implements NotificationChannelInterface
{
    public function code(): string
    {
        return 'LOG';
    }

    public function deliver(NotificationMessage $message): string
    {
        logger()->info('ODAF notification', [
            'event' => $message->eventCode,
            'subscription' => $message->subscriptionCode,
            'recipientUserId' => $message->recipientUserId,
            'recipientAddress' => $message->recipientAddress,
            'subject' => $message->subject,
            'body' => $message->body,
            'entityKey' => $message->entityKey,
        ]);

        return 'SENT';
    }
}
