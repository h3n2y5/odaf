<?php

declare(strict_types=1);

namespace App\Livewire\Studio\Designer;

use App\Support\StudioAccess;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Application Dashboard - Visual entry point untuk ODAF Studio Designer (F3).
 * 
 * Menampilkan applications sebagai cards dengan stats & quick actions,
 * menggantikan tampilan table-based di Studio Data Manager.
 */
#[Layout('layouts.odaf')]
final class ApplicationDashboard extends Component
{
    use NormalizesRows;

    /** @var array<int, array<string, mixed>> */
    public array $applications = [];

    public function mount(StudioAccess $access): void
    {
        $access->ensureAdmin();
        
        // Load applications dengan stats
        $this->loadApplications();
    }

    public function render()
    {
        return view('livewire.studio.designer.application-dashboard', [
            'applications' => $this->applications,
        ]);
    }

    private function loadApplications(): void
    {
        // Query applications dengan stats (simplified - no module/menu counts if tables don't exist)
        $apps = DB::select("
            SELECT 
                RAWTOHEX(APP.OBJECT_ID) AS ID,
                APP.OBJECT_CODE,
                APP.OBJECT_NAME,
                APP.DESCRIPTION,
                APP.STATUS,
                APP.CREATED_AT,
                APP.UPDATED_AT,
                (SELECT COUNT(*) 
                 FROM APP_MODULE M 
                 WHERE M.APPLICATION_ID = APP.OBJECT_ID) AS MODULE_COUNT,
                (SELECT COUNT(*) 
                 FROM APP_MENU MN 
                 WHERE MN.MODULE_ID IN (
                     SELECT M.OBJECT_ID FROM APP_MODULE M WHERE M.APPLICATION_ID = APP.OBJECT_ID
                 )) AS MENU_COUNT,
                (SELECT COUNT(DISTINCT P.OBJECT_ID) 
                 FROM UI_PAGE P 
                 WHERE P.APPLICATION_ID = APP.OBJECT_ID) AS PAGE_COUNT,
                (SELECT COUNT(*) 
                 FROM UI_FIELD F 
                 JOIN UI_PAGE P ON F.PAGE_ID = P.OBJECT_ID 
                 WHERE P.APPLICATION_ID = APP.OBJECT_ID) AS FIELD_COUNT,
                (SELECT COUNT(*) 
                 FROM DS_LOV L 
                 WHERE L.OBJECT_ID IN (
                     SELECT F.LOV_ID FROM UI_FIELD F 
                     JOIN UI_PAGE P ON F.PAGE_ID = P.OBJECT_ID 
                     WHERE P.APPLICATION_ID = APP.OBJECT_ID AND F.LOV_ID IS NOT NULL
                 )) AS LOV_COUNT,
                (SELECT PKG.PACKAGE_VERSION 
                 FROM RT_PACKAGE PKG 
                 WHERE PKG.APPLICATION_ID = APP.OBJECT_ID 
                 AND PKG.ACTIVE_FLAG = 1 
                 FETCH FIRST 1 ROWS ONLY) AS ACTIVE_VERSION,
                (SELECT RAWTOHEX(P2.OBJECT_ID) 
                 FROM UI_PAGE P2 
                 WHERE P2.APPLICATION_ID = APP.OBJECT_ID 
                 ORDER BY P2.OBJECT_CODE 
                 FETCH FIRST 1 ROWS ONLY) AS FIRST_PAGE_ID
            FROM APP_APPLICATION APP
            ORDER BY APP.OBJECT_NAME
        ");

        $this->applications = $this->normalizeRows($apps);
    }

    public function createApplication(): void
    {
        // Redirect ke wizard (akan dibuat nanti)
        $this->redirect('/studio/designer/app/new', navigate: true);
    }

    public function openApp(string $appId): void
    {
        // Get first page of the app to open form builder
        $firstPage = DB::selectOne("
            SELECT RAWTOHEX(P.OBJECT_ID) AS PAGE_ID
            FROM UI_PAGE P
            WHERE P.APPLICATION_ID = HEXTORAW(?)
            ORDER BY P.OBJECT_CODE
            FETCH FIRST 1 ROWS ONLY
        ", [$appId]);

        if ($firstPage) {
            $pageId = $this->normalizeRow($firstPage)['PAGE_ID'];
            $this->redirect("/studio/designer/form/{$pageId}", navigate: true);
        } else {
            // No pages yet - redirect to app overview (stub for now)
            $this->redirect("/studio/designer/app/{$appId}", navigate: true);
        }
    }

    public function compileApp(string $appId): void
    {
        try {
            // Get app code from ID
            $row = DB::selectOne("SELECT OBJECT_CODE FROM APP_APPLICATION WHERE OBJECT_ID = HEXTORAW(?)", [$appId]);
            
            if (!$row) {
                session()->flash('error', 'Application not found.');
                return;
            }

            $appCode = $this->normalizeRow($row)['OBJECT_CODE'];

            // Compile via artisan command
            \Artisan::call('odaf:compile', [
                'application' => $appCode,
                '--activate' => true,
            ]);

            session()->flash('success', "Application {$appCode} compiled successfully!");
            $this->loadApplications(); // Reload untuk update stats
        } catch (\Throwable $e) {
            session()->flash('error', 'Compilation failed: ' . $e->getMessage());
        }
    }
}
