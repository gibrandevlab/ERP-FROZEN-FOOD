<?php

namespace App\Console\Commands;

use App\Services\FinancialReportService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * SendWeeklyReport — Kirim laporan keuangan MINGGUAN ke Telegram.
 * Jadwal: Setiap Minggu malam (akhir minggu) jam 21:00 WIB.
 *
 * Usage:
 *   php artisan report:weekly                  → Laporan minggu ini (Senin-Minggu)
 *   php artisan report:weekly --no-telegram    → Hanya generate PDF
 */
class SendWeeklyReport extends Command
{
    protected $signature = 'report:weekly
                            {--no-telegram : Hanya generate PDF tanpa kirim ke Telegram}';

    protected $description = 'Generate & kirim laporan keuangan MINGGUAN ke Telegram';

    public function handle(): int
    {
        $reportService   = new FinancialReportService();
        $telegramService = new TelegramService();

        // Minggu ini: Senin terakhir s.d. Minggu ini (hari ini)
        $endDate   = Carbon::now();
        $startDate = $endDate->copy()->startOfWeek(Carbon::MONDAY);

        $this->info("📊 Generating laporan mingguan: {$startDate->format('d/m/Y')} – {$endDate->format('d/m/Y')}...");
        $weeklyPath = $reportService->generateWeeklyPdf($startDate, $endDate);
        $this->info("   ✅ PDF berhasil: {$weeklyPath}");

        if (!$this->option('no-telegram')) {
            $weeklyData = $reportService->getWeeklyData($startDate, $endDate);

            $caption = "📊 <b>Laporan Mingguan</b>\n"
                     . "📅 {$startDate->translatedFormat('d M')} — {$endDate->translatedFormat('d M Y')}\n\n"
                     . "💰 Pemasukan: Rp " . number_format($weeklyData['total_income'], 0, ',', '.') . "\n"
                     . "💸 Pengeluaran: Rp " . number_format($weeklyData['total_expense'], 0, ',', '.') . "\n"
                     . "📈 Laba: " . ($weeklyData['laba'] >= 0 ? '' : '-') . "Rp " . number_format(abs($weeklyData['laba']), 0, ',', '.') . "\n";

            if ($weeklyData['total_receivable'] > 0) {
                $caption .= "⏳ Piutang: Rp " . number_format($weeklyData['total_receivable'], 0, ',', '.') . "\n";
            }
            if ($weeklyData['total_payable'] > 0) {
                $caption .= "🔴 Utang: Rp " . number_format($weeklyData['total_payable'], 0, ',', '.') . "\n";
            }

            $caption .= "\n📄 Detail selengkapnya ada di PDF terlampir.";

            $sent = $telegramService->sendDocument($weeklyPath, $caption);
            $this->info($sent ? '   ✅ Terkirim ke Telegram!' : '   ❌ Gagal kirim ke Telegram.');
        }

        $this->newLine();
        $this->info('🎉 Selesai!');
        return self::SUCCESS;
    }
}
