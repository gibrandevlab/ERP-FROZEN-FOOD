<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
|
| Sistem autentikasi UMKM SaaS:
|
| - Login & Register → standar
| - Reset Password   → BUKAN via email, tapi via Kata Rahasia (recovery phrase)
| - Email Verification → TIDAK DIGUNAKAN
|
*/

Route::middleware('guest')->group(function () {

    // Registrasi akun baru (termasuk input kata rahasia)
    Volt::route('register', 'pages.auth.register')
        ->name('register');

    // Login
    Volt::route('login', 'pages.auth.login')
        ->name('login');

    // Pulihkan akun via kata rahasia (menggantikan forgot-password via email)
    // Route name 'password.request' dipertahankan agar link di halaman login tetap berfungsi
    Volt::route('pulihkan-akun', 'pages.auth.forgot-password')
        ->name('password.request');

    // Route lama reset-password/{token} tetap ada tapi redirect ke pulihkan-akun
    Volt::route('reset-password/{token}', 'pages.auth.reset-password')
        ->name('password.reset');
});

Route::middleware('auth')->group(function () {

    // Konfirmasi password sebelum aksi sensitif
    Volt::route('confirm-password', 'pages.auth.confirm-password')
        ->name('password.confirm');

    // Logout
    Route::post('logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});
