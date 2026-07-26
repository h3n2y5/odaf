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

    Route::get('/scanner', \App\Livewire\Runtime\QrScanner::class)->name('odaf.scanner');

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

Route::get('/debug-page', function () {
    $pages = \Illuminate\Support\Facades\DB::select("
        SELECT p.OBJECT_CODE, p.PAGE_TYPE, RAWTOHEX(p.DATASET_ID) AS DATASET_ID, d.OBJECT_CODE AS DS_CODE, d.SOURCE_OBJECT
        FROM ODAF.UI_PAGE p
        LEFT JOIN ODAF.DS_DATASET d ON p.DATASET_ID = d.OBJECT_ID
        WHERE p.APPLICATION_ID = HEXTORAW('F8057A478EBE23C28E3C0FAF49267558')
    ");
    return response()->json($pages);
});

Route::get('/debug-cache', function () {
    $graph = \Illuminate\Support\Facades\Cache::get('odaf.runtime.nexus');
    if (!$graph) {
        $graph = \Illuminate\Support\Facades\Cache::get('odaf.runtime.NEXUS');
    }
    
    // In Odaf\Runtime\UnifiedRuntimeKernel, the cache key is 'odaf.app.' . $appId
    // Let's just find the exact cache key by inspecting Kernel.
    $appCodeRow = \Illuminate\Support\Facades\DB::selectOne("SELECT RAWTOHEX(OBJECT_ID) as id FROM ODAF.APP_APPLICATION WHERE UPPER(OBJECT_CODE) = 'NEXUS'");
    if (!$appCodeRow) return 'App not found';
    
    $cacheKey = 'odaf.graph.' . strtolower($appCodeRow->id);
    $cacheKey2 = 'odaf.graph.' . strtoupper($appCodeRow->id);
    
    $data1 = \Illuminate\Support\Facades\Cache::get($cacheKey);
    $data2 = \Illuminate\Support\Facades\Cache::get($cacheKey2);
    
    return response()->json([
        'key1' => $cacheKey,
        'has1' => $data1 !== null,
        'ds1' => $data1 ? collect($data1['datasets'])->where('code', 'DS_TRX_SALES')->first() : null,
        'key2' => $cacheKey2,
        'has2' => $data2 !== null,
        'ds2' => $data2 ? collect($data2['datasets'])->where('code', 'DS_TRX_SALES')->first() : null,
        'ds_all' => $data1 ? collect($data1['datasets'])->pluck('sourceObject') : null
    ]);
});

Route::get('/test-calc', function () {
    $form = new \App\Livewire\Runtime\DatasetForm();
    $form->form = [
        'HARGA_JUAL' => 200,
        'PAJAK_PERSEN' => 10,
        'HARGA_AKHIR' => null
    ];
    
    $json = \Illuminate\Support\Facades\DB::selectOne("SELECT PAYLOAD FROM ODAF.RT_PACKAGE WHERE ACTIVE_FLAG = 1")->payload;
    if (is_resource($json)) $json = stream_get_contents($json);
    $data = json_decode($json, true);
    $page = collect($data['pages'])->where('code', 'TRX_SALES')->first();
    
    // Simulate updated
    $form->appCode = 'Nexus';
    $form->pageCode = 'TRX_SALES';
    $form->updated('form.PAJAK_PERSEN', 10);
    
    return response()->json($form->form);
});

Route::get('/manifest.json', function () {
    return response()->json([
        'name' => 'ODAF Runtime',
        'short_name' => 'ODAF',
        'start_url' => '/',
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#4f46e5',
        'icons' => [
            [
                'src' => '/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png'
            ],
            [
                'src' => '/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png'
            ]
        ]
    ]);
})->name('pwa.manifest');

Route::get('/app/{appCode}/manifest.json', function (string $appCode) {
    $app = \Illuminate\Support\Facades\DB::selectOne("SELECT OBJECT_NAME FROM ODAF.APP_APPLICATION WHERE OBJECT_CODE = ?", [$appCode]);
    $name = $app ? $app->object_name : 'ODAF App';

    return response()->json([
        'name' => $name,
        'short_name' => substr($name, 0, 12),
        'start_url' => "/app/{$appCode}",
        'display' => 'standalone',
        'background_color' => '#ffffff',
        'theme_color' => '#4f46e5',
        'icons' => [
            [
                'src' => '/icon-192.png',
                'sizes' => '192x192',
                'type' => 'image/png'
            ],
            [
                'src' => '/icon-512.png',
                'sizes' => '512x512',
                'type' => 'image/png'
            ]
        ]
    ]);
})->name('pwa.app.manifest');

