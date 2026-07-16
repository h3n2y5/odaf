<?php

declare(strict_types=1);

use App\Livewire\Auth\Login;
use App\Livewire\Manual\ManualPage;
use App\Livewire\Runtime\AppHome;
use App\Livewire\Runtime\DatasetForm;
use App\Livewire\Runtime\DatasetGrid;
use App\Livewire\Studio\Designer\ApplicationDashboard;
use App\Livewire\Studio\Designer\ApplicationOverview;
use App\Livewire\Studio\Designer\FormBuilder;
use App\Livewire\Studio\Designer\LovDesigner;
use App\Livewire\Studio\Designer\AccessControl;
use App\Livewire\Studio\StudioForm;
use App\Livewire\Studio\StudioGrid;
use App\Livewire\Studio\StudioHome;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Runtime ODAF: seluruh layar (menu, grid, form) digenerate dari Runtime
| Package terkompilasi (BB-03/04/05). Tidak ada route per-entity.
*/

// Root mengarah ke halaman pemilihan aplikasi (butuh autentikasi).
Route::get('/', \App\Livewire\Runtime\AppLauncher::class)->name('odaf.launcher')->middleware('auth');

// Info platform (diagnostik) dipindahkan ke /status.
Route::get('/status', fn () => response()->json([
    'platform' => 'ODAF / MDAF',
    'phase' => 'F2 - Workflow',
    'status' => 'ok',
    'app' => url('/app/ODAF_DEMO'),
]));

// --- Autentikasi ------------------------------------------------------------
Route::get('/login', Login::class)->name('login')->middleware('guest');

Route::post('/logout', function () {
    Auth::guard('web')->logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->name('logout');

// --- Runtime (butuh autentikasi) --------------------------------------------
Route::middleware('auth')->group(function (): void {
    // Manual pengguna (dapat dibaca semua user; edit khusus superuser).
    Route::get('/manual', ManualPage::class)->name('manual');

    // Shell aplikasi (menu dari package).
    Route::get('/app/{appCode}', AppHome::class)->name('odaf.home');

    // Grid generik (list) untuk sebuah halaman berdataset.
    Route::get('/app/{appCode}/g/{pageCode}', DatasetGrid::class)->name('odaf.grid');

    // Form generik: create (tanpa key) / update (dengan key).
    Route::get('/app/{appCode}/f/{pageCode}/{key?}', DatasetForm::class)->name('odaf.form');

    // --- ODAF Studio Data Manager (CRUD generik semua tabel, admin only) ----
    Route::get('/studio', StudioHome::class)->name('studio.home');
    Route::get('/studio/sql', \App\Livewire\Studio\SqlRunner::class)->name('studio.sql');
    Route::get('/studio/t/{table}', StudioGrid::class)->name('studio.grid');
    Route::get('/studio/t/{table}/edit/{key?}', StudioForm::class)->name('studio.form');
    
    // --- ODAF Studio Visual Designer (F3 - Metadata authoring visual) --------
    Route::get('/studio/designer', ApplicationDashboard::class)->name('studio.designer');
    Route::get('/studio/designer/app/new', \App\Livewire\Studio\Designer\AppWizard::class)->name('studio.designer.app.new');
    Route::get('/studio/designer/app/{appId}', ApplicationOverview::class)->name('studio.designer.app.overview');
    Route::get('/studio/designer/form/{pageId}', FormBuilder::class)->name('studio.designer.form');
    Route::get('/studio/designer/workflow/{datasetId}', \App\Livewire\Studio\Designer\WorkflowDesigner::class)->name('studio.designer.workflow');
    Route::get('/studio/designer/lov', fn () => redirect()->route('studio.grid', ['table' => 'DS_LOV']))->name('studio.designer.lov.list');
    Route::get('/studio/designer/lov/new', LovDesigner::class)->name('studio.designer.lov.new');
    Route::get('/studio/designer/lov/{lovId}', LovDesigner::class)->name('studio.designer.lov.edit');
    Route::get('/studio/designer/access', AccessControl::class)->name('studio.designer.access');
    Route::get('/studio/designer/roles-users', \App\Livewire\Studio\Designer\RoleUserManager::class)->name('studio.designer.roles-users');
});

Route::get('/health/db', function () {
    try {
        $value = \Illuminate\Support\Facades\DB::connection()
            ->selectOne('SELECT 1 AS OK FROM DUAL');

        return response()->json([
            'connection' => config('database.default'),
            'oracle' => 'reachable',
            'result' => $value,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'connection' => config('database.default'),
            'oracle' => 'unreachable',
            'error' => $e->getMessage(),
        ], 500);
    }
});

