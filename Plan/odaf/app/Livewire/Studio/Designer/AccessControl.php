<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\AccessControlRepository;
use App\Support\StudioAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * ODAF Studio - Access Control (RBAC berbutir-halus).
 *
 * Admin memilih Role + Aplikasi, lalu menetapkan tingkat akses per HALAMAN
 * (mengatur juga visibilitas menu) dan per FIELD:
 *   (Default) | Penuh | Hanya baca | Samarkan (rahasia) | Tidak ada akses
 * Mendukung masa berlaku (VALID_FROM/VALID_TO) per aturan dan hierarki role
 * (PARENT_ROLE_ID). Disimpan ke SEC_ACCESS dan langsung berlaku di runtime
 * (tanpa compile).
 */
#[Layout('layouts.odaf')]
final class AccessControl extends Component
{
    public string $roleId = '';

    public string $appId = '';

    /** @var array<string, string> level per objek, kunci "TYPE:TARGET_ID" */
    public array $levels = [];

    /** @var array<string, string> VALID_FROM per page, kunci "PAGE:TARGET_ID" */
    public array $validFroms = [];

    /** @var array<string, string> VALID_TO per page, kunci "PAGE:TARGET_ID" */
    public array $validTos = [];

    /** @var string parent role ID untuk hierarki */
    public string $parentRoleId = '';

    public function mount(StudioAccess $access, AccessControlRepository $repo): void
    {
        $access->ensureAdmin();
        $repo->ensureInstalled();
    }

    public function updatedRoleId(AccessControlRepository $repo): void
    {
        $this->loadState($repo);
    }

    public function updatedAppId(AccessControlRepository $repo): void
    {
        $this->roleId = '';
        $this->loadState($repo);
    }

    private function loadState(AccessControlRepository $repo): void
    {
        $this->levels = [];
        $this->validFroms = [];
        $this->validTos = [];
        $this->parentRoleId = '';

        if ($this->roleId === '') {
            return;
        }

        // Prefill level + validitas dari aturan role saat ini.
        $rules = $repo->rulesForRole($this->roleId);
        foreach ($rules as $key => $rule) {
            $this->levels[$key] = $rule['level'] ?? '';
            if (! empty($rule['validFrom'])) {
                $this->validFroms[$key] = $rule['validFrom'];
            }
            if (! empty($rule['validTo'])) {
                $this->validTos[$key] = $rule['validTo'];
            }
        }

        // Load parent role saat ini.
        $roles = $repo->roles();
        foreach ($roles as $role) {
            if (strtoupper((string) $role['ID']) === strtoupper($this->roleId)) {
                $this->parentRoleId = (string) ($role['PARENT_ROLE_ID'] ?? '');
                break;
            }
        }
    }

    public function save(StudioAccess $access, AccessControlRepository $repo): void
    {
        $access->ensureAdmin();

        if ($this->roleId === '' || $this->appId === '') {
            session()->flash('ac_error', 'Pilih role dan aplikasi terlebih dahulu.');

            return;
        }

        try {
            $userId = $access->userId();
            foreach ($repo->pageTree($this->appId) as $page) {
                $pageKey = 'PAGE:'.strtoupper((string) $page['ID']);
                $repo->setRule(
                    $this->roleId,
                    'PAGE',
                    (string) $page['ID'],
                    (string) ($this->levels[$pageKey] ?? ''),
                    $userId,
                    ! empty($this->validFroms[$pageKey]) ? $this->validFroms[$pageKey] : null,
                    ! empty($this->validTos[$pageKey]) ? $this->validTos[$pageKey] : null,
                );

                foreach ($page['FIELDS'] as $field) {
                    $fk = 'FIELD:'.strtoupper((string) $field['ID']);
                    $repo->setRule(
                        $this->roleId,
                        'FIELD',
                        (string) $field['ID'],
                        (string) ($this->levels[$fk] ?? ''),
                        $userId,
                        // Field mewarisi validitas dari page-nya.
                        ! empty($this->validFroms[$pageKey]) ? $this->validFroms[$pageKey] : null,
                        ! empty($this->validTos[$pageKey]) ? $this->validTos[$pageKey] : null,
                    );
                }
            }

            session()->flash('ac_status', 'Aturan akses disimpan. Perubahan langsung berlaku di runtime.');
        } catch (Throwable $e) {
            session()->flash('ac_error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function saveHierarchy(StudioAccess $access, AccessControlRepository $repo): void
    {
        $access->ensureAdmin();

        if ($this->roleId === '') {
            return;
        }

        try {
            $parentId = $this->parentRoleId !== '' ? $this->parentRoleId : null;
            $repo->setRoleParent($this->roleId, $parentId);
            session()->flash('ac_status', 'Hierarki role berhasil diperbarui.');
        } catch (Throwable $e) {
            session()->flash('ac_error', $e->getMessage());
        }
    }

    public function render(AccessControlRepository $repo)
    {
        $tree = ($this->appId !== '') ? $repo->pageTree($this->appId) : [];
        $roles = ($this->appId !== '') ? $repo->rolesForApp($this->appId) : $repo->roles();

        // Bangun peta parent untuk tampilkan info hierarki.
        $roleMap = [];
        foreach ($roles as $r) {
            $roleMap[strtoupper((string) $r['ID'])] = $r;
        }

        // Info parent dari role terpilih.
        $currentRole = $roleMap[strtoupper($this->roleId)] ?? null;
        $parentInfo = null;
        if ($currentRole !== null && ! empty($currentRole['PARENT_ROLE_ID'])) {
            $parentInfo = $roleMap[strtoupper((string) $currentRole['PARENT_ROLE_ID'])] ?? null;
        }

        return view('livewire.studio.designer.access-control', [
            'roles' => $roles,
            'applications' => $repo->applications(),
            'tree' => $tree,
            'roleMap' => $roleMap,
            'parentInfo' => $parentInfo,
        ]);
    }
}
