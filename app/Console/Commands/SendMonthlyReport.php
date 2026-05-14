<?php

namespace App\Console\Commands;

use App\Services\FinancialReportService;
use App\Services\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * SendMonthlyReport — Kirim laporan keuangan BULANAN ke Telegram.
 * Jadwal: Setiap tanggal terakhir bulan, jam 23:00 WIB.
 *
 * Usage:
 *   php artisan report:monthly                 → Laporan bulan ini
 *   php artisan report:monthly --no-telegram   → Hanya generate PDF
 */
class SendMonthlyReport extends Command
{
    protected $signature = 'report:monthly
                            {--no-telegram : Hanya generate PDF tanpa kirim ke Telegram}';

    protected $description = 'Generate & kirim laporan keuangan BULANAN ke Telegram';

    public function handle(): int
    {
        $reportService   = new FinancialReportService();
        $telegramService = new TelegramService();

        // Bulan ini (bukan bulan lalu, karena command ini berjalan di akhir bulan)
        $month = Carbon::now();

        $this->info("📊 Generating laporan bulanan: {$month->translatedFormat('F Y')}...");
        $monthlyPath = $reportService->generateMonthlyPdf($month);
        $this->info("   ✅ PDF berhasil: {$monthlyPath}");

        if (!$this->option('no-telegram')) {
            $monthlyData = $reportService->getMonthlyData($month);

            $caption = "📊 <b>Laporan Bulanan — {$month->translatedFormat('F Y')}</b>\n\n"
                     . "💰 Total Pemasukan: Rp " . number_format($monthlyData['total_income'], 0, ',', '.') . "\n"
                     . "💸 Total Pengeluaran: Rp " . number_format($monthlyData['total_expense'], 0, ',', '.') . "\n"
                     . "📈 Laba Bersih: " . ($monthlyData['laba'] >= 0 ? '' : '-') . "Rp " . number_format(abs($monthlyData['laba']), 0, ',', '.') . "\n";

            if ($monthlyData['total_receivable'] > 0) {
                $caption .= "⏳ Total Piutang: Rp " . number_format($monthlyData['total_receivable'], 0, ',', '.') . "\n";
            }
            if ($monthlyData['total_payable'] > 0) {
                $caption .= "🔴 Total Utang: Rp " . number_format($monthlyData['total_payable'], 0, ',', '.') . "\n";
            }

            $caption .= "\n📄 Detail selengkapnya ada di PDF terlampir.";

            $sent = $telegramService->sendDocument($monthlyPath, $caption);
            $this->info($sent ? '   ✅ Terkirim ke Telegram!' : '   ❌ Gagal kirim ke Telegram.');
        }

        $this->newLine();
        $this->info('🎉 Selesai!');
        return self::SUCCESS;
    }
}
