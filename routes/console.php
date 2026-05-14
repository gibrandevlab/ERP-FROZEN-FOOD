<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── Laporan Keuangan Otomatis ke Telegram ────────────────────────

// 1. HARIAN → Setiap hari jam 05:00 WIB, kirim rekap transaksi kemarin
Schedule::command('report:daily')
    ->dailyAt('05:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-telegram.log'));

// 2. MINGGUAN → Setiap Minggu malam jam 21:00 WIB, kirim rekap minggu ini
Schedule::command('report:weekly')
    ->weeklyOn(0, '21:00') // 0 = Minggu
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-telegram.log'));

// 3. BULANAN → Setiap tanggal terakhir bulan jam 23:00 WIB
Schedule::command('report:monthly')
    ->lastDayOfMonth('23:00')
    ->timezone('Asia/Jakarta')
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/report-telegram.log'));
