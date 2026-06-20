<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ─── Halaman Publik ───────────────────────────────────────────────────────────
Route::redirect('/', '/login');
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Volt::route('/dashboard', 'dashboard.index')
        ->name('dashboard');

    // ── Stok / Produk ────────────────────────────────────────────────────────
    // Middleware 'can:view-stok' memblokir akses di level route (403)
    // sebelum komponen Livewire dimuat. Ini lapisan keamanan PERTAMA.
    Route::prefix('stok')->name('stok.')->middleware('can:view-stok')->group(function () {
        Volt::route('/histori', 'stok.histori')->name('histori');
        Volt::route('/', 'stok.index')->name('index');
        Volt::route('/tambah', 'stok.form')->name('tambah')->middleware('can:create-stok');
        Volt::route('/{slug}/edit', 'stok.form')->name('edit')->middleware('can:edit-stok');
        Volt::route('/{slug}', 'stok.detail')->name('detail');
    });

    // ── Kategori ───────────────────────────────────────────────────────────
    Route::prefix('kategori')->name('kategori.')->middleware('can:view-kategori')->group(function () {
        Volt::route('/', 'kategori.index')->name('index');
        Volt::route('/tambah', 'kategori.form')->name('tambah')->middleware('can:create-kategori');
        Volt::route('/{slug}/edit', 'kategori.form')->name('edit')->middleware('can:edit-kategori');
    });

    // ── Lokasi Stok ────────────────────────────────────────────────────────
    Route::prefix('lokasi')->name('lokasi.')->middleware('can:view-lokasi')->group(function () {
        Volt::route('/', 'lokasi.index')->name('index');
        Volt::route('/tambah', 'lokasi.form')->name('tambah')->middleware('can:create-lokasi');
        Volt::route('/{id}/edit', 'lokasi.form')->name('edit')->middleware('can:edit-lokasi');
    });

    // ── Pembukuan ─────────────────────────────────────────────────────────
    Route::prefix('pembukuan')->name('pembukuan.')->middleware('can:view-pembukuan')->group(function () {
        // PENTING: /ringkasan harus SEBELUM /{slug}
        Volt::route('/ringkasan', 'pembukuan.ringkasan')->name('ringkasan')->middleware('can:view-ringkasan');
        Volt::route('/histori', 'pembukuan.histori')->name('histori');
        Volt::route('/', 'pembukuan.index')->name('index');
        Volt::route('/tambah', 'pembukuan.form')->name('tambah')->middleware('can:create-pembukuan');
        Volt::route('/{slug}/edit', 'pembukuan.form')->name('edit')->middleware('can:edit-pembukuan');
    });

    // ── Pelanggan ─────────────────────────────────────────────────────────
    Route::prefix('pelanggan')->name('pelanggan.')->middleware('can:view-pelanggan')->group(function () {
        Volt::route('/', 'pelanggan.index')->name('index');
    });

    // ── Supplier ──────────────────────────────────────────────────────────
    Route::prefix('supplier')->name('supplier.')->middleware('can:view-supplier')->group(function () {
        Volt::route('/', 'supplier.index')->name('index');
    });

    // ── SPK (Sistem Pendukung Keputusan) ──────────────────────────────────
    Volt::route('/spk', 'spk.index')->name('spk.index')->middleware('can:view-spk');

    // ── Admin Panel ────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:view-pengguna')->group(function () {
        Volt::route('/pengguna', 'admin.pengguna.index')->name('pengguna.index');
        Volt::route('/pengguna/{id}/hak-akses', 'admin.pengguna.hak-akses')->name('pengguna.hak-akses');
    });

    // Profile
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__ . '/auth.php';
