<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController; 
use App\Http\Controllers\Admin\CpclController;
use App\Http\Controllers\Admin\FuzzyController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\Admin\KriteriaController;
use App\Http\Controllers\Uptd\DashboardController as UptdDashboardController;

// 1. Route Root (Cek Login & Role)
Route::get('/', function(){
    if (Auth::check()) {
        if (Auth::user()->role === 'admin') {
            return redirect()->route('admin.dashboard');
        } elseif (Auth::user()->role === 'uptd') {
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
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // USER MANAGEMENT
    Route::get('/user', [UserManagementController::class, 'index'])->name('user-management.index'); 
    Route::get('/user/create', [UserManagementController::class, 'create'])->name('user-management.create');
    Route::post('/user', [UserManagementController::class, 'store'])->name('user-management.store');
    Route::get('/user/{id}/edit', [UserManagementController::class, 'edit'])->name('user-management.edit');
    Route::put('/user/{id}', [UserManagementController::class, 'update'])->name('user-management.update');
    Route::delete('/user/{id}', [UserManagementController::class, 'destroy'])->name('user-management.destroy');

    // Konfigurasi Fuzzy
    Route::get('/kriteria', [KriteriaController::class, 'index'])->name('kriteria.index');
    Route::post('/kriteria', [KriteriaController::class, 'store'])->name('kriteria.store');
    Route::put('/kriteria/{id}', [KriteriaController::class, 'update'])->name('kriteria.update');
    Route::delete('/kriteria/{id}', [KriteriaController::class, 'destroy'])->name('kriteria.destroy');
    Route::get('/sub-kriteria', [KriteriaController::class, 'subKriteria'])->name('sub-kriteria.index');
    Route::post('/sub-kriteria', [KriteriaController::class, 'storeSub'])->name('sub-kriteria.store');
    Route::put('/sub-kriteria/{id}', [KriteriaController::class, 'updateSub'])->name('sub-kriteria.update');
    Route::delete('/sub-kriteria/{id}', [KriteriaController::class, 'destroySub'])->name('sub-kriteria.destroy');
    Route::get('/aturan', [FuzzyController::class, 'rule'])->name('aturan.index'); 
    
    Route::get('/perhitungan/{id?}', [FuzzyController::class, 'perhitungan'])->name('perhitungan.index');
    Route::post('/perhitungan/proses', [FuzzyController::class, 'proses'])->name('perhitungan.proses');
    Route::get('/perhitungan/detail/{id}', [FuzzyController::class, 'detail'])->name('perhitungan.detail');
    
    // Fitur Lain
    Route::get('/cpcl', [CpclController::class, 'index'])->name('cpcl.index'); 
    Route::get('/cpcl/terverifikasi', [CpclController::class, 'verified'])->name('cpcl.verifikasi');
    Route::get('/cpcl/belum-verifikasi', [CpclController::class, 'belum'])->name('cpcl.belum-verifikasi');
    Route::get('/tambah', [CpclController::class, 'create'])->name('add.cpcl');
    Route::post('/tambah', [CpclController::class, 'store'])->name('cpcl.store');
    Route::get('/cpcl/{id}/edit', [CpclController::class, 'edit'])->name('cpcl.edit');
    Route::put('/cpcl/{id}', [CpclController::class, 'update'])->name('cpcl.update');
    Route::delete('/cpcl/{id}', [CpclController::class, 'destroy'])
    ->name('cpcl.destroy');
    Route::get('/hasil', [FuzzyController::class, 'hasil'])->name('hasil.index');
    Route::get('/detail/{id}', [CpclController::class, 'detail'])->name('cpcl.show');
    Route::get('/verifikasi/{id}', [CpclController::class, 'showVerification'])->name('cpcl.verify');
    Route::post('/verifikasi/{id}', [CpclController::class, 'verify'])->name('cpcl.verify.process');


});


// =========================================================================
//  GROUP 2: KHUSUS UPTD (Disesuaikan Hanya CPCL & Laporan)
// =========================================================================
Route::prefix('uptd')->name('uptd.')->middleware(['auth', 'role:uptd'])->group(function () {
    
    // Dashboard UPTD
    Route::get('/dashboard', [UptdDashboardController::class, 'index'])->name('dashboard');

    // Menu 1: Data CPCL
    Route::get('/cpcl', [CpclController::class, 'index'])->name('cpcl.index'); 
    Route::get('/cpcl/create', [CpclController::class, 'create'])->name('cpcl.create'); 
    Route::post('/cpcl', [CpclController::class, 'store'])->name('cpcl.store');
    Route::get('/cpcl/{id}', [CpclController::class, 'detail'])->name('cpcl.show'); 
    Route::get('/cpcl/{id}/edit', [CpclController::class, 'edit'])->name('cpcl.edit');
    Route::put('/cpcl/{id}', [CpclController::class, 'update'])->name('cpcl.update');
    Route::delete('/cpcl/{id}', [CpclController::class, 'destroy'])
    ->name('cpcl.destroy');

    // Menu 2: Laporan Akhir
    Route::get('/laporan', [CpclController::class, 'laporan'])->name('laporan.index');
    
    // Catatan: Route perhitungan dihapus sesuai permintaan menu sidebar yang baru
});