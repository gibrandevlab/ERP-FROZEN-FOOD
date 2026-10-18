<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// ─── Halaman Publik ───────────────────────────────────────────────────────────
Route::redirect('/', '/login');
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Volt::route('/dashboard', 'dashboard.index')
        ->name('dashboard')
        ->middleware('can:view-dashboard');

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

    // ── Purchase Request ───────────────────────────────────────────────────
    Route::prefix('purchase-request')->name('purchase-request.')->middleware('can:view-purchase_request')->group(function () {
        Volt::route('/', 'purchase-request.index')->name('index');
    });

    // ── Persetujuan Pembelian (RBAC terpisah dari pembuatan PR) ───────────
    Route::prefix('purchase-approval')->name('purchase-approval.')->middleware('can:view-purchase_approval')->group(function () {
        Volt::route('/', 'purchase-approval.index')->name('index');
    });

    // ── Forecast Restock ───────────────────────────────────────────────────
    Volt::route('/forecast', 'forecast.index')->name('forecast.index')->middleware('can:view-forecast');

    // ── SPK (Sistem Pendukung Keputusan) ──────────────────────────────────
    Volt::route('/spk', 'spk.index')->name('spk.index')->middleware('can:view-spk');

    // ── Admin Panel ────────────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware('can:view-pengguna')->group(function () {
        Volt::route('/pengguna', 'admin.pengguna.index')->name('pengguna.index');
        Volt::route('/pengguna/tambah', 'admin.pengguna.form')->name('pengguna.tambah')->middleware('can:create-pengguna');
        Volt::route('/pengguna/{id}/edit', 'admin.pengguna.form')->name('pengguna.edit')->middleware('can:edit-pengguna');
        Volt::route('/pengguna/{id}/hak-akses', 'admin.pengguna.hak-akses')->name('pengguna.hak-akses');

        // Token Usage Management
        Volt::route('/token-usage', 'admin.token-usage.index')->name('token-usage.index');
    });

    // ── AI Assistant & Chat Session ────────────────────────────────────────
    Volt::route('/chat/{conversation?}', 'chat.index')->name('chat.index');

    // Profile
    Route::view('profile', 'profile')->name('profile');
});

require __DIR__ . '/auth.php';
