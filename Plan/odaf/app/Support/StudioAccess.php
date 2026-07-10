<?php

declare(strict_types=1);

namespace App\Support;

use App\Auth\OdafUser;
use Illuminate\Support\Facades\Auth;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;
use Odaf\Runtime\ExecutionContext;

/**
 * Konteks & penjaga akses untuk ODAF Studio.
 *
 * Studio adalah tooling administratif (mengedit metadata & data lintas tabel),
 * sehingga hanya boleh diakses superuser (role ADMIN).
 */
final class StudioAccess
{
    public function __construct(private readonly SecurityEngineInterface $security) {}

    public function context(): ExecutionContext
    {
        $user = Auth::guard('web')->user();

        return new ExecutionContext(
            applicationId: 'STUDIO',
            userId: $user?->getAuthIdentifier(),
            roleIds: $user instanceof OdafUser ? $user->roleIds() : [],
        );
    }

    public function isAdmin(): bool
    {
        return $this->security->isSuperuser($this->context());
    }

    /**
     * Batalkan request dengan 403 bila bukan admin.
     */
    public function ensureAdmin(): void
    {
        if (! $this->isAdmin()) {
            abort(403, 'ODAF Studio hanya untuk administrator.');
        }
    }

    public function userId(): ?string
    {
        return $this->context()->userId();
    }
}
