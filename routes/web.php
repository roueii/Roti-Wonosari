<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\FrontlinerController;
use App\Http\Controllers\ProduksiController;
use App\Http\Controllers\SalesMarketingController;
use App\Http\Controllers\StoreManagerController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\KpiFormController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| URL utama tiap halaman + nama route-nya. URL lama/alternatif ada di
| bagian "Alias" paling bawah dan cukup diarahkan (redirect) ke URL utama.
*/

Route::view('/', 'landingpage')->name('home');

// Autentikasi
Route::controller(AuthController::class)->group(function () {
    Route::get('/login', 'showLogin')->name('login');
    Route::post('/login', 'login')->name('login.post');
    Route::match(['get', 'post'], '/logout', 'logout')->name('logout');
});

/**
 * Form Isi Capaian sebuah role: dibuat otomatis dari indikator jabatan di Backend (KpiFormController).
 * URL: {prefix}/isi-capaian/{langkah?}; nama route: {name}.kpi, {name}.kpi.post, {name}.kpi.draft
 */
$registerKpiForm = function (string $prefix, string $name): void {
    Route::get("{$prefix}/isi-capaian/{step?}", [KpiFormController::class, 'step'])->name("{$name}.kpi");
    Route::post("{$prefix}/isi-capaian/{step}", [KpiFormController::class, 'save'])->name("{$name}.kpi.post");
    Route::post("{$prefix}/isi-capaian-draf", [KpiFormController::class, 'draft'])->name("{$name}.kpi.draft");
};

/** Daftarkan halaman riwayat, detail riwayat, dan profil sebuah role. */
$registerCommon = function (string $prefix, string $name): void {
    Route::get("{$prefix}/riwayat", 'riwayatCapaian')->name("{$name}.riwayat");
    Route::get("{$prefix}/riwayat/detail", 'detailRiwayat')->name("{$name}.riwayat.detail");
    Route::get("{$prefix}/riwayat/{id}", 'detailRiwayat')->whereNumber('id');
    Route::get("{$prefix}/profil", 'profil')->name("{$name}.profile");
};

// =========================================================================
// ADMIN
// =========================================================================
Route::prefix('admin')->name('admin.')->middleware('masuk:admin')->controller(AdminController::class)->group(function () {
    Route::get('/', 'dashboard')->name('dashboard');
    Route::get('/tinjau-revisi', 'revisi')->name('revisi');
    Route::post('/tinjau-revisi/tindak-lanjut', 'tindakLanjutRevisi')->name('revisi.tindak_lanjut');
    Route::get('/approve-laporan', 'approveLaporan')->name('approve');
    Route::post('/approve-laporan/{userId}/{date}', 'putuskanLaporan')->whereNumber('userId')->where('date', '\d{4}-\d{2}-\d{2}')->name('approve.decide');
    Route::get('/profil', 'profile')->name('profile');
    Route::post('/profil/email', 'ubahEmail')->name('profile.email');
    Route::get('/rekap-bulanan', 'rekapBulanan')->name('rekap');
    Route::get('/rekap-bulanan/rincian-harian', 'rincianHarian')->name('rekap.rincian');

    Route::get('/kelola-akun', 'kelolaAkun')->name('accounts');
    Route::post('/kelola-akun', 'simpanAkun')->name('accounts.store');
    Route::post('/kelola-akun/{id}', 'ubahAkun')->whereNumber('id')->name('accounts.update');
    Route::post('/kelola-akun/{id}/status', 'ubahStatusAkun')->whereNumber('id')->name('accounts.status');
    Route::post('/kelola-akun/{id}/kata-sandi', 'resetKataSandi')->whereNumber('id')->name('accounts.password');
    Route::post('/kelola-akun/{id}/hapus', 'hapusAkun')->whereNumber('id')->name('accounts.destroy');
});

// Ganti kata sandi sendiri dari halaman Profil (semua role yang sudah login)
Route::post('/profil/kata-sandi', [\App\Http\Controllers\KataSandiController::class, 'ganti'])->middleware('masuk')->name('profil.kata-sandi');

// Kelola KPI Admin (tersambung ke katalog indikator & indikator per jabatan di Backend)
Route::prefix('admin/kelola-kpi')->name('admin.kpi')->middleware('masuk:admin')->controller(\App\Http\Controllers\KelolaKpiController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'store')->name('.store');
    Route::post('/{indicator}', 'update')->whereNumber('indicator')->name('.update');
    Route::post('/{indicator}/nonaktifkan', 'nonaktifkan')->whereNumber('indicator')->name('.deactivate');
    Route::post('/{indicator}/aktifkan', 'aktifkan')->whereNumber('indicator')->name('.activate');
    Route::post('/{indicator}/hapus', 'hapus')->whereNumber('indicator')->name('.destroy');
});

// =========================================================================
// STORE MANAGER
// =========================================================================
Route::middleware('masuk:store_manager')->controller(StoreManagerController::class)->group(function () use ($registerKpiForm, $registerCommon) {
    Route::get('/store-manager', 'dashboard')->name('storemanager.dashboard');

    $registerKpiForm('/store-manager', 'storemanager');

    Route::get('/store-manager/training', 'training')->name('storemanager.training');
    Route::post('/store-manager/training', 'simpanTraining')->name('storemanager.training.post');

    Route::get('/store-manager/approval', 'approvalTim')->name('storemanager.approval');
    Route::post('/store-manager/approval/setujui-semua', 'approveAllCapaian')->name('storemanager.approval.approve_all');
    Route::post('/store-manager/approval/{id}/setujui', 'approveCapaian')->name('storemanager.approval.approve');
    Route::post('/store-manager/approval/{id}/tolak', 'rejectCapaian')->name('storemanager.approval.reject');

    $registerCommon('/store-manager', 'storemanager');
});

// =========================================================================
// FRONTLINER (URL /karyawan)
// =========================================================================
Route::middleware('masuk:frontliner')->controller(FrontlinerController::class)->group(function () use ($registerKpiForm, $registerCommon) {
    Route::get('/karyawan', 'dashboard')->name('staff.dashboard');
    Route::get('/frontliner', 'dashboard')->name('frontliner.dashboard');

    $registerKpiForm('/karyawan', 'frontliner');

    $registerCommon('/karyawan', 'frontliner');
});

// =========================================================================
// DRIVER
// =========================================================================
Route::middleware('masuk:driver')->controller(DriverController::class)->group(function () use ($registerKpiForm, $registerCommon) {
    Route::get('/driver', 'dashboard')->name('driver.dashboard');

    $registerKpiForm('/driver', 'driver');

    $registerCommon('/driver', 'driver');
});

// =========================================================================
// PRODUKSI
// =========================================================================
Route::middleware('masuk:produksi')->controller(ProduksiController::class)->group(function () use ($registerKpiForm, $registerCommon) {
    Route::get('/produksi', 'dashboard')->name('produksi.dashboard');

    $registerKpiForm('/produksi', 'produksi');

    $registerCommon('/produksi', 'produksi');
});

// =========================================================================
// SALES & MARKETING DAN SUPERVISOR
// Memakai ChecklistRoleController + view bersama "checklist.*".
// Slug langkah diambil dari roleConfig(), mis. /sales-marketing/isi-capaian/penjualan-customer
// =========================================================================
foreach ([
    ['sales-marketing', 'sales', SalesMarketingController::class, 'sales_marketing'],
    ['supervisor', 'supervisor', SupervisorController::class, 'supervisor'],
] as [$prefix, $name, $controller, $role]) {
    Route::middleware("masuk:{$role}")->group(fn () => $registerKpiForm("/{$prefix}", $name));
    Route::prefix($prefix)->name("{$name}.")->middleware("masuk:{$role}")->controller($controller)->group(function () {
        Route::get('/', 'dashboard')->name('dashboard');
        Route::get('/riwayat', 'riwayat')->name('riwayat');
        Route::get('/riwayat/{id}', 'detailRiwayat')->whereNumber('id')->name('riwayat.detail');
        Route::get('/profil', 'profil')->name('profile');
    });
}

// =========================================================================
// ALIAS: URL lama / alternatif -> URL utama
// =========================================================================
$aliases = [
    // Admin
    '/profil' => '/admin/profil',
    '/admin/profile' => '/admin/profil',
    '/admin/revisi' => '/admin/tinjau-revisi',
    '/admin/accounts' => '/admin/kelola-akun',
    '/admin/akun' => '/admin/kelola-akun',
    '/admin/kpi' => '/admin/kelola-kpi',
    '/admin/approve' => '/admin/approve-laporan',
    '/admin/approval' => '/admin/approve-laporan',

    // Store Manager
    '/storemanager' => '/store-manager',
    '/store-manager/tim' => '/store-manager?tab=tim',
    '/store-manager/approval-tim' => '/store-manager/approval',
    '/store-manager/profile' => '/store-manager/profil',
    '/storemanager/profil' => '/store-manager/profil',
    '/storemanager/profile' => '/store-manager/profil',
    '/storemanager/riwayat' => '/store-manager/riwayat',
    '/storemanager/riwayat/detail' => '/store-manager/riwayat/detail',
    '/storemanager/riwayat/{id}' => '/store-manager/riwayat/{id}',

    // Frontliner
    '/frontliner/riwayat' => '/karyawan/riwayat',
    '/staff/riwayat' => '/karyawan/riwayat',
    '/frontliner/riwayat/detail' => '/karyawan/riwayat/detail',
    '/staff/riwayat/detail' => '/karyawan/riwayat/detail',
    '/frontliner/riwayat/{id}' => '/karyawan/riwayat/{id}',
    '/karyawan/profile' => '/karyawan/profil',
    '/frontliner/profil' => '/karyawan/profil',
    '/frontliner/profile' => '/karyawan/profil',
    '/staff/profil' => '/karyawan/profil',

    // Driver & Produksi (juga dapat diakses lewat /karyawan/driver/... dan /karyawan/produksi/...)
    '/karyawan-driver' => '/driver',
    '/driver/profile' => '/driver/profil',
    '/karyawan-produksi' => '/produksi',
    '/produksi/profile' => '/produksi/profil',

    // Sales & Supervisor
    '/sales' => '/sales-marketing',
    '/marketing' => '/sales-marketing',
    '/spv' => '/supervisor',
];

// /karyawan/driver/* -> /driver/*  dan  /karyawan/produksi/* -> /produksi/*
foreach (['driver', 'produksi'] as $role) {
    foreach (['', '/riwayat', '/riwayat/detail', '/riwayat/{id}', '/profil', '/profile'] as $path) {
        $aliases["/karyawan/{$role}{$path}"] = "/{$role}" . ($path === '/profile' ? '/profil' : $path);
    }
}

// URL langkah form lama (mis. /driver/pengiriman) -> awal form Isi Capaian
// Format: [prefix alias, prefix tujuan, [slug alias => slug tujuan]]
$frontlinerSteps = [
    'penjualan' => 'penjualan', 'pelayanan' => 'pelayanan', 'product-knowledge' => 'product-knowledge',
    'akurasi-kasir' => 'akurasi-kasir', 'display-kebersihan' => 'display-kebersihan', 'kepatuhan-sop' => 'kepatuhan-sop',
];
$driverSteps = array_combine($s = ['pengiriman', 'administrasi', 'kendaraan', 'pelayanan'], $s);
$produksiSteps = array_combine($s = ['hasil-produksi', 'waktu-alat', 'kehadiran-disiplin', 'kepatuhan-sop', 'kebersihan-area'], $s);

$stepAliases = [
    ['/store-manager', '/store-manager', array_combine($s = ['penjualan', 'laba-persediaan', 'pelayanan', 'operasional-sdm', 'promosi-event'], $s)],
    ['/karyawan', '/karyawan', $frontlinerSteps + ['kasir' => 'akurasi-kasir', 'kepatuhan' => 'kepatuhan-sop']],
    ['/karyawan/isi-capaian', '/karyawan', ['knowledge' => 'product-knowledge', 'kasir' => 'akurasi-kasir', 'display' => 'display-kebersihan', 'kepatuhan' => 'kepatuhan-sop']],
    ['/frontliner', '/karyawan', $frontlinerSteps + ['knowledge' => 'product-knowledge']],
    ['/frontliner/isi-capaian', '/karyawan', $frontlinerSteps],
    ['/driver', '/driver', $driverSteps],
    ['/karyawan/driver', '/driver', $driverSteps],
    ['/produksi', '/produksi', $produksiSteps],
    ['/karyawan/produksi', '/produksi', $produksiSteps],
];

foreach ($stepAliases as [$from, $to, $slugs]) {
    foreach ($slugs as $alias => $slug) {
        $aliases["{$from}/{$alias}"] = "{$to}/isi-capaian"; // langkah kini mengikuti kelompok Kelola KPI
    }
}

foreach ($aliases as $from => $to) {
    Route::redirect($from, $to);
}
