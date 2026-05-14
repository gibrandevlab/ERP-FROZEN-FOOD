<?php

namespace App\Console\Commands;

use App\Services\FinancialReportService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * SendDailyReport — Kirim laporan keuangan HARIAN ke Telegram.
 * Jadwal: Setiap hari jam 05:00 WIB → mengirim rekap transaksi KEMARIN.
 *
 * Usage:
 *   php artisan report:daily                     → Laporan kemarin
 *   php artisan report:daily --date=2026-05-01   → Laporan tanggal tertentu
 *   php artisan report:daily --no-telegram       → Hanya generate PDF
 */
class SendDailyReport extends Command
{
    protected $signature = 'report:daily
                            {--date= : Tanggal laporan (YYYY-MM-DD). Default: kemarin}
                            {--no-telegram : Hanya generate PDF tanpa kirim ke Telegram}';

    protected $description = 'Generate & kirim laporan keuangan HARIAN ke Telegram';

    public function handle(): int
    {
        $reportService   = new FinancialReportService();
        $telegramService = new TelegramService();

        $dateStr = $this->option('date') ?: Carbon::yesterday()->toDateString();
        $date    = Carbon::parse($dateStr);

        $this->info("📊 Generating laporan harian: {$date->format('d/m/Y')}...");
        $dailyPath = $reportService->generateDailyPdf($date);
        $this->info("   ✅ PDF berhasil: {$dailyPath}");

        if (!$this->option('no-telegram')) {
            $dailyData = $reportService->getDailyData($date);

            $caption = "📊 <b>Laporan Harian — {$date->translatedFormat('d F Y')}</b>\n\n"
                     . "💰 Pemasukan: Rp " . number_format($dailyData['total_income'], 0, ',', '.') . "\n"
                     . "💸 Pengeluaran: Rp " . number_format($dailyData['total_expense'], 0, ',', '.') . "\n"
                     . "📈 Laba: " . ($dailyData['laba'] >= 0 ? '' : '-') . "Rp " . number_format(abs($dailyData['laba']), 0, ',', '.') . "\n";

            if ($dailyData['total_receivable'] > 0) {
                $caption .= "⏳ Piutang: Rp " . number_format($dailyData['total_receivable'], 0, ',', '.') . "\n";
            }
            if ($dailyData['total_payable'] > 0) {
                $caption .= "🔴 Utang: Rp " . number_format($dailyData['total_payable'], 0, ',', '.') . "\n";
            }

            $caption .= "\n📄 Detail lengkap ada di PDF terlampir.";

            $sent = $telegramService->sendDocument($dailyPath, $caption);
            $this->info($sent ? '   ✅ Terkirim ke Telegram!' : '   ❌ Gagal kirim ke Telegram.');
        }

        $this->newLine();
        $this->info('🎉 Selesai!');
        return self::SUCCESS;
    }
}
