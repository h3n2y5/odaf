<?php

declare(strict_types=1);

namespace App\Livewire\Runtime;

use App\Auth\OdafUser;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Odaf\Engine\Notification\Contracts\NotificationEngineInterface;
use Odaf\Runtime\ExecutionContext;

/**
 * Bell notifikasi in-app di header. Menampilkan jumlah belum dibaca dan daftar
 * notifikasi terbaru untuk pengguna aktif (RT_NOTIFICATION). Tidak memerlukan
 * package runtime — hanya identitas pengguna.
 */
final class NotificationBell extends Component
{
    public bool $open = false;

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(string $id, NotificationEngineInterface $notifications): void
    {
        $notifications->markRead($this->context(), $id);
    }

    public function markAllRead(NotificationEngineInterface $notifications): void
    {
        $notifications->markAllRead($this->context());
    }

    public function render(NotificationEngineInterface $notifications)
    {
        $context = $this->context();

        return view('livewire.runtime.notification-bell', [
            'unread' => $notifications->unreadCount($context),
            'items' => $this->open ? $notifications->inbox($context, 10) : [],
        ]);
    }

    private function context(): ExecutionContext
    {
        $user = Auth::guard('web')->user();

        return new ExecutionContext(
            applicationId: 'ODAF_DEMO',
            userId: $user?->getAuthIdentifier(),
            roleIds: $user instanceof OdafUser ? $user->roleIds() : [],
        );
    }
}
