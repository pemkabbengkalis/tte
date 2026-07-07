<?php

use App\Http\Controllers\DownloadController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect(match (Auth::user()->role->value) {
        'verifikator' => '/verifikator',
        'admin'       => '/admin',
        default       => '/pemohon',
    });
})->middleware(['auth', 'aktif'])->name('dashboard');

Route::middleware(['auth', 'aktif'])->group(function () {

    Route::get('/template/surat-permohonan', [DownloadController::class, 'template'])
        ->name('template.download');

    Route::get('/dokumen/{dokumen}/lihat', [DownloadController::class, 'dokumenLihat'])
        ->name('dokumen.lihat');
    Route::get('/dokumen/{dokumen}/unduh', [DownloadController::class, 'dokumenUnduh'])
        ->name('dokumen.unduh');

    Route::livewire('/notifikasi', 'pages::notifikasi.daftar')
        ->name('notifikasi.daftar');

    // ===== Profile (semua peran) =====
    Route::livewire('/profile', 'pages::profile.index')
        ->name('profile.index');

    // ===== Pemohon =====
    Route::middleware('role:pemohon')->group(function () {
        Route::livewire('/pemohon', 'pages::pemohon.dashboard')
            ->name('pemohon.dashboard');

        Route::livewire('/pemohon/buat-permohonan/{permohonan?}', 'pages::pemohon.buat-permohonan')
            ->name('pemohon.buat');

        Route::livewire('/pemohon/permohonan/{permohonan}', 'pages::pemohon.detail-permohonan')
            ->name('pemohon.detail');

        Route::livewire('/pemohon/permohonan/{permohonan}/perbaiki', 'pages::pemohon.perbaiki-berkas')
            ->name('pemohon.perbaiki');
    });

    // ===== Verifikator =====
    Route::middleware('role:verifikator')->group(function () {
        Route::livewire('/verifikator', 'pages::verifikator.dashboard')
            ->name('verifikator.dashboard');

        Route::livewire('/verifikator/template', 'pages::verifikator.template')
            ->name('verifikator.template');

        Route::livewire('/verifikator/permohonan/{permohonan}', 'pages::verifikator.detail-permohonan')
            ->name('verifikator.detail');
    });

    // ===== Admin =====
    Route::middleware('role:admin')->group(function () {
        Route::livewire('/admin', 'pages::admin.dashboard')
            ->name('admin.dashboard');

        Route::livewire('/admin/template', 'pages::admin.template')
            ->name('admin.template');

        Route::livewire('/admin/pengguna', 'pages::admin.pengguna.index')
            ->name('admin.pengguna.index');

        Route::livewire('/admin/pengguna/{user}/edit', 'pages::admin.pengguna.edit')
            ->name('admin.pengguna.edit');
    });

    Route::livewire('/akun', 'pages::akun.index')
        ->middleware('role:verifikator,admin')
        ->name('akun.index');
});

require __DIR__ . '/auth.php';