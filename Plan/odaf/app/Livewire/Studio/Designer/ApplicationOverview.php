<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Odaf\Studio\MetadataScaffolder;

/**
 * Application Overview - daftar form (UI_PAGE) dalam sebuah aplikasi.
 *
 * Menjadi langkah antara Dashboard -> pilih Form -> Form Builder,
 * sehingga pengguna dapat memilih halaman mana yang ingin didesain.
 * Juga tempat membuat menu/tabel transaksi baru (prefix T_).
 */
#[Layout('layouts.odaf')]
final class ApplicationOverview extends Component
{
    use NormalizesRows;

    public string $appId;

    /** @var array<string, mixed> */
    public array $application = [];

    /** @var array<int, array<string, mixed>> */
    public array $pages = [];

    /** Dialog buat menu/tabel baru. */
    public bool $showNewModal = false;
    public string $newEntityName = '';

    /** Dialog buat header-detail. */
    public bool $showHdModal = false;
    public string $hdHeaderName = '';
    public string $hdDetailName = '';

    public function mount(StudioAccess $access, string $appId): void
    {
        $access->ensureAdmin();

        $this->appId = $appId;
        $this->loadApplication();
        $this->loadPages();
    }

    public function render()
    {
        return view('livewire.studio.designer.application-overview');
    }

    public function openNewModal(): void
    {
        $this->resetErrorBag();
        $this->newEntityName = '';
        $this->showNewModal = true;
    }

    public function closeNewModal(): void
    {
        $this->showNewModal = false;
    }

    /**
     * Preview nama tabel: "Supplier" -> "T_SUPPLIER".
     */
    #[Computed]
    public function tableNamePreview(): string
    {
        $entity = MetadataScaffolder::standardizeEntityName($this->newEntityName);
        if ($entity === '') return '';
        
        $appCode = (string) ($this->application['OBJECT_CODE'] ?? '');
        return $appCode ? strtoupper($appCode) . '_T_' . $entity : 'T_' . $entity;
    }

    /**
     * Buat menu/tabel transaksi baru (tabel fisik T_<nama> + metadata + menu),
     * lalu kompilasi & buka Form Builder-nya.
     */
    public function createTable(MetadataScaffolder $scaffolder): void
    {
        $this->validate(
            ['newEntityName' => 'required|string|max:100'],
            [],
            ['newEntityName' => 'Nama menu/tabel'],
        );

        if ($this->tableNamePreview === '') {
            $this->addError('newEntityName', 'Nama tidak valid untuk dijadikan nama tabel.');
            return;
        }

        $appCode = (string) $this->application['OBJECT_CODE'];

        try {
            $result = $scaffolder->createTransactionTable([
                'name' => $this->newEntityName,
                'appCode' => $appCode,
            ]);

            // Kompilasi & aktifkan agar menu/form langsung tersedia di runtime.
            \Artisan::call('odaf:compile', [
                'application' => $appCode,
                '--activate' => true,
            ]);

            // Ambil ID halaman baru untuk diarahkan ke Form Builder.
            $row = DB::selectOne(
                "SELECT RAWTOHEX(OBJECT_ID) AS ID FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)",
                [$result['pageCode'], $this->appId]
            );

            $this->showNewModal = false;
            session()->flash('success', "Tabel {$result['datasetCode']} & menu {$result['menuCode']} berhasil dibuat (tabel {$result['table']}).");

            if ($row) {
                $pageId = $this->normalizeRow($row)['ID'];
                $this->redirect("/studio/designer/form/{$pageId}", navigate: true);
                return;
            }

            $this->loadPages();
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal membuat tabel/menu: ' . $e->getMessage());
        }
    }

    public function openHdModal(): void
    {
        $this->resetErrorBag();
        $this->hdHeaderName = '';
        $this->hdDetailName = '';
        $this->showHdModal = true;
    }

    public function closeHdModal(): void
    {
        $this->showHdModal = false;
    }

    /**
     * Buat pasangan header-detail (mis. Purchase Order + PO Line) + kompilasi.
     */
    public function createHeaderDetail(MetadataScaffolder $scaffolder): void
    {
        $this->validate([
            'hdHeaderName' => 'required|string|max:100',
            'hdDetailName' => 'required|string|max:100',
        ], [], [
            'hdHeaderName' => 'Nama header',
            'hdDetailName' => 'Nama detail',
        ]);

        $appCode = (string) $this->application['OBJECT_CODE'];

        try {
            $result = $scaffolder->createHeaderDetail([
                'headerName' => $this->hdHeaderName,
                'detailName' => $this->hdDetailName,
                'appCode' => $appCode,
                'detailLabel' => $this->hdDetailName,
            ]);

            \Artisan::call('odaf:compile', ['application' => $appCode, '--activate' => true]);

            $row = DB::selectOne(
                "SELECT RAWTOHEX(OBJECT_ID) AS ID FROM UI_PAGE WHERE OBJECT_CODE = ? AND APPLICATION_ID = HEXTORAW(?)",
                [$result['headerPageCode'], $this->appId]
            );

            $this->showHdModal = false;
            session()->flash('success', "Header-detail dibuat: header {$result['header']['table']} + detail {$result['detail']['table']} (FK {$result['fkColumn']}).");

            if ($row) {
                $pageId = $this->normalizeRow($row)['ID'];
                $this->redirect("/studio/designer/form/{$pageId}", navigate: true);
                return;
            }

            $this->loadPages();
        } catch (\Throwable $e) {
            session()->flash('error', 'Gagal membuat header-detail: ' . $e->getMessage());
        }
    }

    private function cleanName(string $name): string
    {
        return MetadataScaffolder::standardizeEntityName($name);
    }

    private function loadApplication(): void
    {
        $app = DB::selectOne("
            SELECT
                RAWTOHEX(OBJECT_ID) AS ID,
                OBJECT_CODE,
                OBJECT_NAME,
                DESCRIPTION,
                STATUS
            FROM APP_APPLICATION
            WHERE OBJECT_ID = HEXTORAW(?)
        ", [$this->appId]);

        if (!$app) {
            abort(404, 'Application not found');
        }

        $this->application = $this->normalizeRow($app);
    }

    public function deletePage(string $pageId): void
    {
        try {
            DB::beginTransaction();

            $pageObj = DB::selectOne("
                SELECT RAWTOHEX(P.OBJECT_ID) AS ID, P.OBJECT_CODE, RAWTOHEX(D.OBJECT_ID) AS DID, D.SOURCE_OBJECT 
                FROM UI_PAGE P
                LEFT JOIN DS_DATASET D ON P.DATASET_ID = D.OBJECT_ID
                WHERE P.OBJECT_ID = HEXTORAW(?)
            ", [$pageId]);

            if (!$pageObj) throw new \Exception("Halaman tidak ditemukan.");
            $page = $this->normalizeRow($pageObj);

            // Hapus aturan akses terkait halaman dan field di dalamnya
            DB::delete("DELETE FROM SEC_ACCESS WHERE OBJECT_TYPE = 'PAGE' AND TARGET_OBJECT_ID = HEXTORAW(?)", [$pageId]);
            DB::delete("DELETE FROM SEC_ACCESS WHERE OBJECT_TYPE = 'FIELD' AND TARGET_OBJECT_ID IN (SELECT OBJECT_ID FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?))", [$pageId]);

            // Hapus menu & validasi
            DB::delete("DELETE FROM APP_MENU WHERE PAGE_ID = HEXTORAW(?)", [$pageId]);
            DB::delete("DELETE FROM VAL_RULE WHERE FIELD_ID IN (SELECT OBJECT_ID FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?))", [$pageId]);
            
            // Hapus field & page
            DB::delete("DELETE FROM UI_FIELD WHERE PAGE_ID = HEXTORAW(?)", [$pageId]);
            DB::delete("DELETE FROM UI_PAGE WHERE OBJECT_ID = HEXTORAW(?)", [$pageId]);

            // Hapus dataset
            if (!empty($page['DID'])) {
                DB::delete("DELETE FROM VAL_RULE WHERE DATASET_ID = HEXTORAW(?)", [$page['DID']]);
                DB::delete("DELETE FROM DS_DATASET WHERE OBJECT_ID = HEXTORAW(?)", [$page['DID']]);
            }

            DB::commit();

            // Drop tabel fisik setelah commit DB berhasil agar tidak terjadi schema mismatch jika rollback
            $tableName = $page['SOURCE_OBJECT'] ?? '';
            if ($tableName && preg_match('/^[A-Z0-9_$#]+$/', $tableName)) {
                try {
                    DB::statement("DROP TABLE {$tableName} CASCADE CONSTRAINTS");
                } catch (\Throwable $e) {
                    // Ignore drop table error (table might not exist)
                }
            }

            session()->flash('success', "Form / Menu beserta tabel fisik berhasil dihapus.");
            $this->loadPages();
        } catch (\Throwable $e) {
            DB::rollBack();
            session()->flash('error', "Gagal menghapus form: " . $e->getMessage());
        }
    }

    private function loadPages(): void
    {
        $pages = DB::select("
            SELECT
                RAWTOHEX(P.OBJECT_ID) AS ID,
                P.OBJECT_CODE,
                P.OBJECT_NAME,
                P.TITLE,
                P.PAGE_TYPE,
                RAWTOHEX(D.OBJECT_ID) AS DATASET_ID,
                D.OBJECT_CODE AS DATASET_CODE,
                (SELECT COUNT(*) FROM UI_FIELD F WHERE F.PAGE_ID = P.OBJECT_ID) AS FIELD_COUNT
            FROM UI_PAGE P
            LEFT JOIN DS_DATASET D ON P.DATASET_ID = D.OBJECT_ID
            WHERE P.APPLICATION_ID = HEXTORAW(?)
            ORDER BY P.OBJECT_CODE
        ", [$this->appId]);

        $this->pages = $this->normalizeRows($pages);
    }
}
