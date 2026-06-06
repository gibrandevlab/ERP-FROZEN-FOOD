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
    // Middleware 'can:view-products' memblokir akses di level route (403)
    // sebelum komponen Livewire dimuat. Ini lapisan keamanan PERTAMA.
    Route::prefix('stok')->name('stok.')->middleware('can:view-products')->group(function () {
        Volt::route('/histori', 'stok.histori')->name('histori');
        Volt::route('/', 'stok.index')->name('index');
        Volt::route('/tambah', 'stok.form')->name('tambah')->middleware('can:create-products');
        Volt::route('/{slug}/edit', 'stok.form')->name('edit')->middleware('can:edit-products');
        Volt::route('/{slug}', 'stok.detail')->name('detail');
    });

    // ── Kategori ───────────────────────────────────────────────────────────
    Route::prefix('kategori')->name('kategori.')->middleware('can:view-categories')->group(function () {
        Volt::route('/', 'kategori.index')->name('index');
        Volt::route('/tambah', 'kategori.form')->name('tambah')->middleware('can:create-categories');
        Volt::route('/{slug}/edit', 'kategori.form')->name('edit')->middleware('can:edit-categories');
    });

    // ── Lokasi Stok ────────────────────────────────────────────────────────
    Route::prefix('lokasi')->name('lokasi.')->middleware('can:view-locations')->group(function () {
        Volt::route('/', 'lokasi.index')->name('index');
        Volt::route('/tambah', 'lokasi.form')->name('tambah')->middleware('can:create-locations');
        Volt::route('/{id}/edit', 'lokasi.form')->name('edit')->middleware('can:edit-locations');
    });

    // ── Pembukuan ─────────────────────────────────────────────────────────
    Route::prefix('pembukuan')->name('pembukuan.')->middleware('can:view-ledger')->group(function () {
        // PENTING: /ringkasan harus SEBELUM /{slug}
        Volt::route('/ringkasan', 'pembukuan.ringkasan')->name('ringkasan');
        Volt::route('/histori', 'pembukuan.histori')->name('histori');
        Volt::route('/', 'pembukuan.index')->name('index');
        Volt::route('/tambah', 'pembukuan.form')->name('tambah')->middleware('can:create-ledger');
        Volt::route('/{slug}/edit', 'pembukuan.form')->name('edit')->middleware('can:edit-ledger');
    });

    // ── Pelanggan ─────────────────────────────────────────────────────────
    Route::prefix('pelanggan')->name('pelanggan.')->group(function () {
        Volt::route('/', 'pelanggan.index')->name('index');
    });

    // ── Supplier ──────────────────────────────────────────────────────────
    Route::prefix('supplier')->name('supplier.')->group(function () {
        Volt::route('/', 'supplier.index')->name('index');
    });

    // ── SPK (Sistem Pendukung Keputusan) ──────────────────────────────────
    Volt::route('/spk', 'spk.index')->name('spk.index');

    // ── Admin Panel ────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:manage-users')->group(function () {
        Volt::route('/pengguna', 'admin.pengguna.index')->name('pengguna.index');
        Volt::route('/pengguna/{id}/hak-akses', 'admin.pengguna.hak-akses')->name('pengguna.hak-akses');
    });

    // Profile
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__ . '/auth.php';
