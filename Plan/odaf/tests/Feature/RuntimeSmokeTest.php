<?php

declare(strict_types=1);

use App\Auth\OdafUser;
use App\Livewire\Runtime\DatasetForm;
use Livewire\Livewire;
use Odaf\Engine\Dataset\Contracts\DatasetEngineInterface;
use Odaf\Engine\Notification\Contracts\NotificationEngineInterface;
use Odaf\Engine\Workflow\Contracts\WorkflowEngineInterface;
use Odaf\Runtime\ExecutionContext;
use Odaf\Runtime\RuntimePackageRepository;
use Odaf\Runtime\UnifiedRuntimeKernel;

/**
 * Smoke test HTTP runtime terhadap package aktif (dijalankan di container dengan
 * Oracle + RT_PACKAGE ODAF_DEMO yang sudah dikompilasi & diaktifkan).
 *
 * Memverifikasi rendering server-side halaman terautentikasi: beranda, grid
 * (query dataset generik), dan form (renderer + panel workflow).
 */
function admin(): OdafUser
{
    return new OdafUser(
        objectId: '00000000000000000000000000000001',
        username: 'admin',
        name: 'System Administrator',
        email: 'admin@odaf.local',
        passwordHash: null,
        roleIds: ['00000000000000000000000000000002'],
    );
}

it('mengalihkan pengguna tak terautentikasi ke login', function (): void {
    $this->get('/app/ODAF_DEMO')->assertRedirect('/login');
});

it('menampilkan beranda aplikasi untuk pengguna terautentikasi', function (): void {
    $this->actingAs(admin())
        ->get('/app/ODAF_DEMO')
        ->assertOk()
        ->assertSee('ODAF Demo Application')
        ->assertSee('Customer');
});

it('merender grid dataset generik (query Oracle)', function (): void {
    $this->actingAs(admin())
        ->get('/app/ODAF_DEMO/g/PAGE_CUSTOMER')
        ->assertOk()
        ->assertSee('Customer')
        ->assertSee('Baru');
});

it('merender form generik dengan field dari metadata', function (): void {
    $this->actingAs(admin())
        ->get('/app/ODAF_DEMO/f/PAGE_CUSTOMER')
        ->assertOk()
        ->assertSee('Kode Pelanggan')
        ->assertSee('Nama Pelanggan');
});

it('membuat customer via form (insert RAW(16)), audit, dan memulai workflow', function (): void {
    $this->actingAs(admin());

    $code = 'SMOKE-'.substr((string) hrtime(true), -8);

    Livewire::test(DatasetForm::class, ['appCode' => 'ODAF_DEMO', 'pageCode' => 'PAGE_CUSTOMER'])
        ->set('form.CUSTOMER_CODE', $code)
        ->set('form.CUSTOMER_NAME', 'Smoke Test Customer')
        ->set('form.CREDIT_LIMIT', 5000)
        ->call('save')
        ->assertHasNoErrors();

    // Baris tersimpan & terlihat di grid (difilter via pencarian agar tak
    // bergantung pada paginasi saat data uji menumpuk).
    $this->get('/app/ODAF_DEMO/g/PAGE_CUSTOMER?q='.$code)
        ->assertOk()
        ->assertSee($code);
});

it('transisi workflow SUBMIT memicu notifikasi in-app untuk approver (BB-10)', function (): void {
    $app = app();

    // Boot package ke kernel (dibagikan lintas engine via singleton).
    $package = $app->make(RuntimePackageRepository::class)->loadActiveByCode('ODAF_DEMO');
    $app->make(UnifiedRuntimeKernel::class)->loadPackage($package);

    $context = new ExecutionContext(
        applicationId: $package->applicationId(),
        userId: '00000000000000000000000000000001',
        roleIds: ['00000000000000000000000000000002'],
    );

    $datasetId = '00000000000000000000000000000020';
    $dataset = $app->make(DatasetEngineInterface::class);
    $workflow = $app->make(WorkflowEngineInterface::class);
    $notifications = $app->make(NotificationEngineInterface::class);

    $code = 'WFN-'.substr((string) hrtime(true), -8);
    $key = $dataset->create($context, $datasetId, ['CUSTOMER_CODE' => $code, 'CUSTOMER_NAME' => 'Workflow Ntf']);
    $workflow->start($context, $datasetId, $key);

    $before = $notifications->unreadCount($context);
    $workflow->perform($context, $datasetId, $key, 'SUBMIT');
    $after = $notifications->unreadCount($context);

    // Admin adalah anggota role ADMIN (approver) -> menerima notifikasi in-app.
    expect($after)->toBeGreaterThan($before);
});
