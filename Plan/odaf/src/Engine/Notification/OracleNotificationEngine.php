<?php

declare(strict_types=1);

namespace Odaf\Engine\Notification;

use Illuminate\Database\ConnectionInterface;
use Odaf\Engine\Notification\Contracts\NotificationChannelInterface;
use Odaf\Engine\Notification\Contracts\NotificationEngineInterface;
use Odaf\Runtime\Contracts\ExecutionContextInterface;
use Odaf\Runtime\UnifiedRuntimeKernel;

/**
 * BB-10 Notification Engine — implementasi Oracle.
 *
 * Mengeksekusi subscription terkompilasi (dari package) untuk sebuah event:
 * resolve subscription -> resolve penerima -> render template -> kirim via
 * channel adapter -> (in-app tercatat di RT_NOTIFICATION). Engine tidak
 * bergantung pada transport channel apa pun (NTF-002/003).
 */
final class OracleNotificationEngine implements NotificationEngineInterface
{
    /** @var array<string, NotificationChannelInterface> */
    private array $channels = [];

    /**
     * @param  iterable<NotificationChannelInterface>  $channels
     */
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly UnifiedRuntimeKernel $kernel,
        iterable $channels = [],
        private readonly string $fallbackChannel = 'LOG',
    ) {
        foreach ($channels as $channel) {
            $this->channels[strtoupper($channel->code())] = $channel;
        }
    }

    public function publish(ExecutionContextInterface $context, string $eventCode, array $payload = []): int
    {
        $appId = $context->applicationId();
        $subscriptions = $this->kernel->subscriptionsForEvent($appId, $eventCode);
        if ($subscriptions === []) {
            return 0;
        }

        $delivered = 0;
        foreach ($subscriptions as $sub) {
            $notification = $this->kernel->notification($appId, (string) $sub['notificationId']);
            if ($notification === null) {
                continue;
            }

            $subject = $this->render((string) $notification['subjectTemplate'], $payload);
            $body = $this->render((string) $notification['bodyTemplate'], $payload);
            $channelCode = strtoupper((string) ($sub['channel'] ?? $notification['channel'] ?? 'IN_APP'));
            $channel = $this->channels[$channelCode] ?? $this->channels[$this->fallbackChannel] ?? null;
            if ($channel === null) {
                continue;
            }

            foreach ($this->resolveRecipients($context, $sub, $payload) as $recipient) {
                $message = new NotificationMessage(
                    subject: $subject,
                    body: $body,
                    recipientUserId: $recipient['userId'],
                    recipientAddress: $recipient['address'],
                    notificationId: (string) $notification['id'],
                    subscriptionCode: (string) $sub['code'],
                    eventCode: $eventCode,
                    entityKey: isset($payload['entityKey']) ? (string) $payload['entityKey'] : null,
                    actorId: $context->userId(),
                );

                if ($channel->deliver($message) === 'SENT') {
                    $delivered++;
                }
            }
        }

        return $delivered;
    }

    public function inbox(ExecutionContextInterface $context, int $limit = 10): array
    {
        $userId = $context->userId();
        if ($userId === null) {
            return [];
        }

        $rows = $this->connection->select(
            "SELECT RAWTOHEX(OBJECT_ID) AS OBJECT_ID, SUBJECT, BODY, EVENT_CODE, READ_FLAG,
                    TO_CHAR(CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT
             FROM RT_NOTIFICATION
             WHERE RECIPIENT_USER_ID = HEXTORAW(?) AND CHANNEL = 'IN_APP'
             ORDER BY CREATED_AT DESC
             FETCH FIRST ? ROWS ONLY",
            [strtoupper($userId), max(1, $limit)],
        );

        return array_map(static function ($r): array {
            $r = array_change_key_case((array) $r, CASE_UPPER);
            $body = $r['BODY'] ?? null;
            if (is_resource($body)) {
                $body = stream_get_contents($body);
            }

            return [
                'id' => (string) ($r['OBJECT_ID'] ?? ''),
                'subject' => (string) ($r['SUBJECT'] ?? ''),
                'body' => (string) ($body ?? ''),
                'event' => $r['EVENT_CODE'] ?? null,
                'read' => (int) ($r['READ_FLAG'] ?? 0) === 1,
                'at' => $r['CREATED_AT'] ?? null,
            ];
        }, $rows);
    }

    public function unreadCount(ExecutionContextInterface $context): int
    {
        $userId = $context->userId();
        if ($userId === null) {
            return 0;
        }

        return (int) $this->connection->scalar(
            "SELECT COUNT(*) FROM RT_NOTIFICATION
             WHERE RECIPIENT_USER_ID = HEXTORAW(?) AND CHANNEL = 'IN_APP' AND READ_FLAG = 0",
            [strtoupper($userId)],
        );
    }

    public function markRead(ExecutionContextInterface $context, string $notificationId): void
    {
        $userId = $context->userId();
        if ($userId === null) {
            return;
        }

        $this->connection->update(
            'UPDATE RT_NOTIFICATION SET READ_FLAG = 1, READ_AT = SYSTIMESTAMP
             WHERE OBJECT_ID = HEXTORAW(?) AND RECIPIENT_USER_ID = HEXTORAW(?)',
            [strtoupper($notificationId), strtoupper($userId)],
        );
    }

    public function markAllRead(ExecutionContextInterface $context): void
    {
        $userId = $context->userId();
        if ($userId === null) {
            return;
        }

        $this->connection->update(
            "UPDATE RT_NOTIFICATION SET READ_FLAG = 1, READ_AT = SYSTIMESTAMP
             WHERE RECIPIENT_USER_ID = HEXTORAW(?) AND CHANNEL = 'IN_APP' AND READ_FLAG = 0",
            [strtoupper($userId)],
        );
    }

    // ---- Helpers ------------------------------------------------------------

    /**
     * Substitusi placeholder {{key}} (toleran spasi) dengan nilai payload.
     *
     * @param  array<string, mixed>  $payload
     */
    private function render(string $template, array $payload): string
    {
        if ($template === '') {
            return '';
        }

        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/',
            static function (array $m) use ($payload): string {
                $value = $payload[$m[1]] ?? '';

                return is_scalar($value) ? (string) $value : '';
            },
            $template,
        );
    }

    /**
     * Resolusi penerima menjadi daftar [ ['userId'=>hex|null, 'address'=>string|null] ].
     *
     * @param  array<string, mixed>  $sub
     * @param  array<string, mixed>  $payload
     * @return array<int, array{userId: string|null, address: string|null}>
     */
    private function resolveRecipients(ExecutionContextInterface $context, array $sub, array $payload): array
    {
        $type = strtoupper((string) $sub['recipientType']);
        $ref = $sub['recipientRef'] ?? null;

        return match ($type) {
            'CURRENT' => $context->userId() !== null
                ? [['userId' => strtoupper($context->userId()), 'address' => null]]
                : [],
            'OWNER' => isset($payload['ownerId']) && $payload['ownerId'] !== null
                ? [['userId' => strtoupper((string) $payload['ownerId']), 'address' => null]]
                : [],
            'USER' => $this->resolveUserByCode((string) $ref),
            'ROLE' => $this->resolveRoleMembers((string) $ref),
            default => [],
        };
    }

    /**
     * @return array<int, array{userId: string|null, address: string|null}>
     */
    private function resolveUserByCode(string $username): array
    {
        if ($username === '') {
            return [];
        }
        $id = $this->connection->scalar(
            'SELECT RAWTOHEX(OBJECT_ID) FROM SEC_USER WHERE OBJECT_CODE = ? AND ACTIVE_FLAG = 1',
            [$username],
        );

        return $id !== null ? [['userId' => strtoupper((string) $id), 'address' => null]] : [];
    }

    /**
     * @return array<int, array{userId: string|null, address: string|null}>
     */
    private function resolveRoleMembers(string $roleCode): array
    {
        if ($roleCode === '') {
            return [];
        }
        $rows = $this->connection->select(
            'SELECT RAWTOHEX(ur.USER_ID) AS MEMBER_ID
             FROM SEC_USER_ROLE ur
             JOIN SEC_ROLE r ON r.OBJECT_ID = ur.ROLE_ID
             JOIN SEC_USER u ON u.OBJECT_ID = ur.USER_ID
             WHERE r.OBJECT_CODE = ? AND u.ACTIVE_FLAG = 1',
            [$roleCode],
        );

        $out = [];
        foreach ($rows as $r) {
            $arr = (array) $r;
            if ($arr !== []) {
                $out[] = ['userId' => strtoupper((string) reset($arr)), 'address' => null];
            }
        }

        return $out;
    }
}
