<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification\Channel;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Notification\Contracts\NotificationChannelInterface;
use Odaf\Engine\Notification\NotificationMessage;
use Odaf\Support\Identity\IdentityGeneratorInterface;
use Throwable;

/**
 * Channel In-App: menyimpan notifikasi ke RT_NOTIFICATION sebagai kotak masuk
 * internal aplikasi (bell/inbox). Penerima harus berupa user (RECIPIENT_USER_ID).
 */
final class InAppChannel implements NotificationChannelInterface
{
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly IdentityGeneratorInterface $identity,
    ) {}

    public function code(): string
    {
        return 'IN_APP';
    }

    public function deliver(NotificationMessage $message): string
    {
        if ($message->recipientUserId === null) {
            return 'FAILED';
        }

        try {
            $this->connection->insert(
                'INSERT INTO RT_NOTIFICATION
                    (OBJECT_ID, NOTIFICATION_ID, SUBSCRIPTION_CODE, EVENT_CODE, CHANNEL, RECIPIENT_USER_ID,
                     SUBJECT, BODY, ENTITY_KEY, DELIVERY_STATUS, READ_FLAG, CREATED_BY)
                 VALUES (HEXTORAW(?), HEXTORAW(?), ?, ?, ?, HEXTORAW(?), ?, ?, ?, ?, 0, HEXTORAW(?))',
                [
                    strtoupper($this->identity->generate()),
                    $message->notificationId !== null ? strtoupper($message->notificationId) : null,
                    $message->subscriptionCode,
                    $message->eventCode,
                    $this->code(),
                    strtoupper($message->recipientUserId),
                    $message->subject,
                    $message->body,
                    $message->entityKey,
                    'SENT',
                    $message->actorId !== null ? strtoupper($message->actorId) : null,
                ],
            );

            return 'SENT';
        } catch (Throwable $e) {
            logger()->error('ODAF notifikasi in-app gagal', ['error' => $e->getMessage()]);

            return 'FAILED';
        }
    }
}
