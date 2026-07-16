<?php

declare(strict_types=1);

namespace App\Support;

use App\Auth\OdafUser;
use Illuminate\Support\Facades\Auth;
use Odaf\Compiler\Contracts\RuntimePackageInterface;
use Odaf\Engine\Security\Contracts\SecurityEngineInterface;
use Odaf\Runtime\ExecutionContext;
use Odaf\Runtime\RuntimePackageRepository;
use Odaf\Runtime\UnifiedRuntimeKernel;
use Odaf\Studio\TableDataManager;
use RuntimeException;

/**
 * Menyatukan bootstrap runtime untuk satu request web.
 *
 * Tugas: memuat Runtime Package aktif sebuah aplikasi ke kernel dan menyusun
 * ExecutionContext dari pengguna terautentikasi (guard 'web' / SEC_USER).
 * Kernel dan engine hanya bekerja atas package terkompilasi (CORE-002).
 */
final class RuntimeSession
{
    public function __construct(
        private readonly RuntimePackageRepository $packages,
        private readonly UnifiedRuntimeKernel $kernel,
        private readonly SecurityEngineInterface $security,
    ) {}

    /**
     * Muat package aktif aplikasi ke kernel dan kembalikan package.
     */
    public function boot(string $applicationCode): RuntimePackageInterface
    {
        $package = $this->packages->loadActiveByCode($applicationCode);
        if ($package === null) {
            throw new RuntimeException(
                "Belum ada Runtime Package aktif untuk aplikasi '{$applicationCode}'. ".
                "Jalankan: php artisan odaf:compile {$applicationCode} --activate",
            );
        }

        $this->kernel->loadPackage($package);

        return $package;
    }

    public function context(string $applicationId): ExecutionContext
    {
        $user = Auth::guard('web')->user();

        $userId = null;
        $roleIds = [];
        if ($user instanceof OdafUser) {
            $userId = $user->getAuthIdentifier();
            $roleIds = $user->roleIds();
        }

        return new ExecutionContext(
            applicationId: $applicationId,
            userId: $userId,
            locale: (string) config('app.locale', 'id'),
            roleIds: $roleIds,
        );
    }

    public function kernel(): UnifiedRuntimeKernel
    {
        return $this->kernel;
    }

    /**
     * Bangun item navigasi dari pohon menu terkompilasi, dengan URL yang sudah
     * di-resolusi (menu -> halaman -> route grid).
     *
     * @return array<int, array<string, mixed>>
     */
    public function navItems(string $applicationCode, string $applicationId): array
    {
        $payload = $this->kernel->payload($applicationId);
        $pages = $payload['pages'] ?? [];
        $context = $this->context($applicationId);
        $isAdmin = $this->security->isSuperuser($context);

        return $this->mapNav($this->kernel->menus($applicationId), $pages, $applicationCode, $isAdmin, $context);
    }

    /**
     * @param  array<int, array<string, mixed>>  $menus
     * @param  array<string, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    private function mapNav(array $menus, array $pages, string $appCode, bool $isAdmin, ExecutionContext $context): array
    {
        $items = [];
        foreach ($menus as $menu) {
            $url = null;
            $pageId = $menu['pageId'] ?? null;

            // Sembunyikan menu bila status BUKAN PUBLISHED (kecuali admin).
            if (! $isAdmin && ($menu['status'] ?? 'PUBLISHED') !== 'PUBLISHED') {
                continue;
            }

            // Sembunyikan menu bila akses halaman terkait = NONE (kecuali admin).
            if ($pageId !== null && ! $isAdmin) {
                $level = $this->security->accessLevel(
                    $context,
                    SecurityEngineInterface::OBJ_PAGE,
                    (string) $pageId,
                );
                if ($level === SecurityEngineInterface::LEVEL_NONE) {
                    continue;
                }
            }

            if ($pageId !== null && isset($pages[$pageId])) {
                $url = route('odaf.grid', ['appCode' => $appCode, 'pageCode' => $pages[$pageId]['code']]);
            }

            // Menu tanpa halaman: sediakan link konfigurasi lanjutan (khusus admin)
            // menuju form Studio untuk mengatur/membuat halaman menu tersebut.
            $configUrl = null;
            if ($url === null && $isAdmin && ($menu['id'] ?? '') !== '') {
                $configUrl = route('studio.form', [
                    'table' => 'APP_MENU',
                    'key' => TableDataManager::encodeKey(['OBJECT_ID' => (string) $menu['id']]),
                ]);
            }

            $children = $this->mapNav($menu['children'] ?? [], $pages, $appCode, $isAdmin, $context);

            // Sembunyikan menu induk (tanpa halaman sendiri) bila semua anaknya
            // tersembunyi karena pembatasan akses.
            if ($url === null && $configUrl === null && $children === [] && ($menu['children'] ?? []) !== []) {
                continue;
            }

            $items[] = [
                'name' => (string) $menu['name'],
                'icon' => $menu['icon'] ?? null,
                'url' => $url,
                'configUrl' => $configUrl,
                'children' => $children,
            ];
        }

        return $items;
    }
}
