<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\CpclController;
use App\Http\Controllers\Admin\FuzzyController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\KriteriaController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Uptd\LaporanController as UptdLaporanController;
use App\Http\Controllers\Uptd\DashboardController as UptdDashboardController;

// 1. Route Root (Cek Login & Role)
Route::get('/', function () {
    if (Auth::check()) {
        $role = Auth::user()->role;
        if ($role === 'admin' || $role === 'admin_pangan' || $role === 'admin_hartibun') {
            return redirect()->route('admin.dashboard');
        } elseif ($role === 'uptd') {
            return redirect()->route('uptd.dashboard');
        }
    }
    return redirect()->route('login');
});

// 2. Auth Routes
Route::get('/login', [AuthController::class, 'login'])->name('login');
Route::post('/login', [AuthController::class, 'loginProses'])->name('login.proses');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


// =========================================================================
//  GROUP 1: KHUSUS ADMIN 
// =========================================================================
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,admin_pangan,admin_hartibun'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // USER MANAGEMENT
    Route::resource('user-management', UserManagementController::class)->except(['show']);
    // Alias jika diperlukan oleh view lama
    Route::get('/user', [UserManagementController::class, 'index'])->name('user-management.index');
    Route::get('user/profil/{id}', [UserManagementController::class, 'profile'])->name('user-management.profile');
    Route::get('user/{id}/edit', [UserManagementController::class, 'edit'])->name('user-management.edit');
    Route::put('user/{id}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::delete('user/{id}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');
    Route::get('user/{id}/edit-profile', [UserManagementController::class, 'editProfile'])->name('user-management.edit-profile');
    Route::put('user/{id}/update-profile', [UserManagementController::class, 'updateProfile'])->name('user-management.update-profile'); 

    // Konfigurasi Fuzzy (Kriteria & Sub)
    Route::resource('kriteria', KriteriaController::class)->only(['index', 'store', 'update', 'destroy']);
    
    Route::get('/sub-kriteria', [KriteriaController::class, 'subKriteria'])->name('sub-kriteria.index');
    Route::post('/sub-kriteria', [KriteriaController::class, 'storeSub'])->name('sub-kriteria.store');
    Route::put('/sub-kriteria/{id}', [KriteriaController::class, 'updateSub'])->name('sub-kriteria.update');
    Route::delete('/sub-kriteria/{id}', [KriteriaController::class, 'destroySub'])->name('sub-kriteria.destroy');
    
    // Perhitungan & Aturan
    Route::get('/aturan', [FuzzyController::class, 'rule'])->name('aturan.index'); 
    Route::get('/perhitungan/{id?}', [FuzzyController::class, 'perhitungan'])->name('perhitungan.index');
    Route::post('/perhitungan/proses', [FuzzyController::class, 'proses'])->name('perhitungan.proses');
    Route::get('/perhitungan/detail/{id}', [FuzzyController::class, 'detail'])->name('perhitungan.detail');
    
    // Fitur CPCL
    Route::get('/cpcl', [CpclController::class, 'index'])->name('cpcl.index'); 
    Route::get('/cpcl/terverifikasi', [CpclController::class, 'verified'])->name('cpcl.verifikasi');
    Route::get('/cpcl/belum-verifikasi', [CpclController::class, 'belum'])->name('cpcl.belum-verifikasi');
    Route::get('/tambah', [CpclController::class, 'create'])->name('add.cpcl');
    Route::post('/tambah', [CpclController::class, 'store'])->name('cpcl.store');
    Route::get('/cpcl/{id}/edit', [CpclController::class, 'edit'])->name('cpcl.edit');
    Route::put('/cpcl/{id}', [CpclController::class, 'update'])->name('cpcl.update');
    Route::delete('/cpcl/{id}', [CpclController::class, 'destroy'])->name('cpcl.destroy');
    
    // Hasil & Verifikasi
    Route::get('/hasil', [FuzzyController::class, 'hasil'])->name('hasil.index');
    Route::get('/detail/{id}', [CpclController::class, 'detail'])->name('cpcl.show');
    Route::get('/verifikasi/{id}', [CpclController::class, 'showVerification'])->name('cpcl.verify');
    Route::post('/verifikasi/{id}', [CpclController::class, 'verify'])->name('cpcl.verify.process');
    Route::get('/perbaikan', [CpclController::class, 'perbaikan'])->name('cpcl.perbaikan');
    Route::get('/ditolak', [CpclController::class, 'ditolak'])->name('cpcl.ditolak');

    // Laporan
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
});


// =========================================================================
//  GROUP 2: KHUSUS UPTD
// =========================================================================
Route::prefix('uptd')->name('uptd.')->middleware(['auth', 'role:uptd'])->group(function () {
    
    Route::get('/dashboard', [UptdDashboardController::class, 'index'])->name('dashboard');

    // Data CPCL (UPTD)
    Route::get('/cpcl', [CpclController::class, 'index'])->name('cpcl.index'); 
    Route::get('/cpcl/create', [CpclController::class, 'create'])->name('cpcl.create'); 
    Route::post('/cpcl', [CpclController::class, 'store'])->name('cpcl.store');
    Route::get('/cpcl/{id}', [CpclController::class, 'detail'])->name('cpcl.show'); 
    Route::get('/cpcl/{id}/edit', [CpclController::class, 'edit'])->name('cpcl.edit');
    Route::put('/cpcl/{id}', [CpclController::class, 'update'])->name('cpcl.update');
    Route::delete('/cpcl/{id}', [CpclController::class, 'destroy'])->name('cpcl.destroy');

    // Laporan & Cetak SK
    Route::get('laporan-sk', [UptdLaporanController::class, 'index'])->name('laporan.index');
    Route::get('laporan-sk/{cpcl}', [UptdLaporanController::class, 'show'])->name('laporan-sk.show');
    Route::get('laporan-sk/{cpcl}/print', [UptdLaporanController::class, 'print'])->name('laporan-sk.print');
    Route::post('laporan-sk/print-bulk', [UptdLaporanController::class, 'printBulk'])->name('laporan-sk.print-bulk');
    Route::post('laporan-sk/{cpcl}/mark-printed', [UptdLaporanController::class, 'markAsPrinted'])->name('laporan-sk.mark-printed');
    Route::get('laporan-sk/{cpcl}/download-pdf', [UptdLaporanController::class, 'downloadPDF'])->name('laporan-sk.download-pdf');
});

// =========================================================================
//  PROXY API WILAYAH (Fix Intelephense "Undefined Method json")
// =========================================================================
Route::prefix('proxy-wilayah')->group(function () {
    
    Route::get('/regencies/{province}', function($province) {
        $url = "https://wilayah.id/api/regencies/{$province}.json";
        
        // Menggunakan stream context untuk bypass SSL jika perlu
        $context = stream_context_create([
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        
        $response = file_get_contents($url, false, $context);
        return response($response)->header('Content-Type', 'application/json');
    });

    Route::get('/districts/{regency}', function($regency) {
        $url = "https://wilayah.id/api/districts/{$regency}.json";
        $context = stream_context_create([
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        
        $response = file_get_contents($url, false, $context);
        return response($response)->header('Content-Type', 'application/json');
    });

    Route::get('/villages/{district}', function($district) {
        $url = "https://wilayah.id/api/villages/{$district}.json";
        $context = stream_context_create([
            "ssl" => ["verify_peer" => false, "verify_peer_name" => false]
        ]);
        
        $response = file_get_contents($url, false, $context);
        return response($response)->header('Content-Type', 'application/json');
    });
});