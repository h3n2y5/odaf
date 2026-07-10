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
 * Disimpan ke SEC_ACCESS dan langsung berlaku di runtime (tanpa compile).
 */
#[Layout('layouts.odaf')]
final class AccessControl extends Component
{
    public string $roleId = '';

    public string $appId = '';

    /** @var array<string, string> level per objek, kunci "TYPE:TARGET_ID" */
    public array $levels = [];

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
        $this->loadState($repo);
    }

    private function loadState(AccessControlRepository $repo): void
    {
        $this->levels = [];
        if ($this->roleId === '') {
            return;
        }
        // Prefill dari aturan role saat ini (objek lain tetap "Default").
        $this->levels = $repo->rulesForRole($this->roleId);
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
                $repo->setRule($this->roleId, 'PAGE', (string) $page['ID'], (string) ($this->levels[$pageKey] ?? ''), $userId);

                foreach ($page['FIELDS'] as $field) {
                    $fk = 'FIELD:'.strtoupper((string) $field['ID']);
                    $repo->setRule($this->roleId, 'FIELD', (string) $field['ID'], (string) ($this->levels[$fk] ?? ''), $userId);
                }
            }

            session()->flash('ac_status', 'Aturan akses disimpan. Perubahan langsung berlaku di runtime.');
        } catch (Throwable $e) {
            session()->flash('ac_error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function render(AccessControlRepository $repo)
    {
        $tree = ($this->appId !== '') ? $repo->pageTree($this->appId) : [];

        return view('livewire.studio.designer.access-control', [
            'roles' => $repo->roles(),
            'applications' => $repo->applications(),
            'tree' => $tree,
        ]);
    }
}
