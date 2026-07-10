<?php

declare(strict_types=1);

namespace App\Livewire\Manual;

use App\Support\ManualRepository;
use App\Support\RuntimeSession;
use App\Support\StudioAccess;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

/**
 * Manual pengguna berbasis web.
 *
 * - Dapat dibaca semua pengguna terautentikasi.
 * - Konten (bagian Markdown) hanya dapat diedit superuser (role ADMIN).
 * - Menautkan langsung ke menu asli (Data Manager, Designer, LOV, Runtime).
 */
#[Layout('layouts.odaf')]
final class ManualPage extends Component
{
    public bool $isAdmin = false;

    public bool $showEditor = false;

    public ?string $editingId = null;

    public string $formTitle = '';

    public string $formBody = '';

    public int $formOrder = 100;

    public function mount(ManualRepository $repo, StudioAccess $access): void
    {
        // Bootstrap idempotent: buat tabel + seed konten default bila perlu.
        $repo->ensureInstalled();
        $this->isAdmin = $access->isAdmin();
    }

    public function render(ManualRepository $repo, RuntimeSession $session)
    {
        $nav = [];
        $appCode = 'ODAF_DEMO';
        $appName = 'ODAF';

        try {
            $package = $session->boot($appCode);
            $appName = $package->toArray()['application']['name'] ?? $appCode;
            $nav = $session->navItems($appCode, $package->applicationId());
        } catch (Throwable) {
            // Runtime package belum aktif: manual tetap dapat dibuka tanpa menu.
        }

        return view('livewire.manual.manual-page', [
            'appCode' => $appCode,
            'appName' => $appName,
            'nav' => $nav,
            'sections' => $repo->sections(),
            'previewHtml' => $this->showEditor ? $repo->renderHtml($this->formBody) : '',
        ]);
    }

    public function newSection(StudioAccess $access): void
    {
        $access->ensureAdmin();
        $this->resetEditor();
        $this->formOrder = 100;
        $this->showEditor = true;
    }

    public function edit(ManualRepository $repo, StudioAccess $access, string $id): void
    {
        $access->ensureAdmin();

        $section = $repo->find($id);
        if ($section === null) {
            session()->flash('manual_error', 'Bagian tidak ditemukan.');

            return;
        }

        $this->editingId = $id;
        $this->formTitle = (string) $section['TITLE'];
        $this->formBody = (string) $section['BODY_MD'];
        $this->formOrder = (int) $section['DISPLAY_ORDER'];
        $this->showEditor = true;
    }

    public function save(ManualRepository $repo, StudioAccess $access): void
    {
        $access->ensureAdmin();

        $this->validate([
            'formTitle' => 'required|string|max:200',
            'formBody' => 'required|string',
            'formOrder' => 'required|integer|min:0|max:100000',
        ], [], [
            'formTitle' => 'judul',
            'formBody' => 'isi',
            'formOrder' => 'urutan',
        ]);

        $userId = $access->userId();
        $title = trim($this->formTitle);

        try {
            if ($this->editingId !== null) {
                $slug = $repo->uniqueSlug($title, $this->editingId);
                $repo->update($this->editingId, $title, $slug, $this->formBody, $this->formOrder, $userId);
                session()->flash('manual_status', 'Bagian diperbarui.');
            } else {
                $slug = $repo->uniqueSlug($title);
                $repo->create($title, $slug, $this->formBody, $this->formOrder, $userId);
                session()->flash('manual_status', 'Bagian baru ditambahkan.');
            }

            $this->resetEditor();
            $this->showEditor = false;
        } catch (Throwable $e) {
            session()->flash('manual_error', 'Gagal menyimpan: '.$e->getMessage());
        }
    }

    public function delete(ManualRepository $repo, StudioAccess $access, string $id): void
    {
        $access->ensureAdmin();

        try {
            $repo->delete($id);
            session()->flash('manual_status', 'Bagian dihapus.');
            if ($this->editingId === $id) {
                $this->resetEditor();
                $this->showEditor = false;
            }
        } catch (Throwable $e) {
            session()->flash('manual_error', 'Gagal menghapus: '.$e->getMessage());
        }
    }

    public function cancel(): void
    {
        $this->resetEditor();
        $this->showEditor = false;
    }

    private function resetEditor(): void
    {
        $this->reset(['editingId', 'formTitle', 'formBody', 'formOrder']);
        $this->resetErrorBag();
    }
}
