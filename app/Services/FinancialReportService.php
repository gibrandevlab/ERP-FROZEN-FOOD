<?php

namespace App\Services;

use App\Models\Ledger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Carbon;

/**
 * FinancialReportService — Generate laporan keuangan harian & bulanan (PDF).
 */
class FinancialReportService
{
    /**
     * Generate data laporan harian (kemarin).
     */
    public function getDailyData(Carbon $date): array
    {
        $dateStr = $date->toDateString();

        // Semua transaksi hari itu
        $ledgers = Ledger::with(['product', 'customer', 'supplier'])
            ->whereDate('date', $dateStr)
            ->orderBy('type')
            ->orderBy('created_at')
            ->get();

        // Pemasukan LUNAS
        $incomesPaid   = $ledgers->where('type', 'income')->where('payment_status', 'paid');
        $totalIncome   = $incomesPaid->sum('amount');

        // Pengeluaran LUNAS
        $expensesPaid  = $ledgers->where('type', 'expense')->where('payment_status', 'paid');
        $totalExpense  = $expensesPaid->sum('amount');

        // Piutang (income unpaid — orang ngutang ke kita)
        $receivables   = $ledgers->where('type', 'income')->where('payment_status', 'unpaid');
        $totalReceivable = $receivables->sum('amount');

        // Utang (expense unpaid — kita ngutang ke supplier)
        $payables      = $ledgers->where('type', 'expense')->where('payment_status', 'unpaid');
        $totalPayable  = $payables->sum('amount');

        $laba = $totalIncome - $totalExpense;

        return [
            'date'              => $date,
            'ledgers'           => $ledgers,
            'total_income'      => $totalIncome,
            'total_expense'     => $totalExpense,
            'total_receivable'  => $totalReceivable,
            'total_payable'     => $totalPayable,
            'laba'              => $laba,
            'incomes_paid'      => $incomesPaid,
            'expenses_paid'     => $expensesPaid,
            'receivables'       => $receivables,
            'payables'          => $payables,
            'total_transactions'=> $ledgers->count(),
        ];
    }

    /**
     * Generate data laporan bulanan (bulan sebelumnya).
     */
    public function getMonthlyData(Carbon $month): array
    {
        $startDate = $month->copy()->startOfMonth()->toDateString();
        $endDate   = $month->copy()->endOfMonth()->toDateString();

        $ledgers = Ledger::with(['product', 'customer', 'supplier'])
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('type')
            ->get();

        // Pemasukan LUNAS
        $incomesPaid   = $ledgers->where('type', 'income')->where('payment_status', 'paid');
        $totalIncome   = $incomesPaid->sum('amount');

        // Pengeluaran LUNAS
        $expensesPaid  = $ledgers->where('type', 'expense')->where('payment_status', 'paid');
        $totalExpense  = $expensesPaid->sum('amount');

        // Piutang
        $receivables   = $ledgers->where('type', 'income')->where('payment_status', 'unpaid');
        $totalReceivable = $receivables->sum('amount');

        // Utang
        $payables      = $ledgers->where('type', 'expense')->where('payment_status', 'unpaid');
        $totalPayable  = $payables->sum('amount');

        // Breakdown penjualan per produk
        $salesByProduct = $incomesPaid->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn($group) => [
                'product_name' => $group->first()->product?->name ?? '-',
                'qty'          => $group->sum('quantity'),
                'total'        => $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();

        // Breakdown pembelian per supplier
        $purchaseBySupplier = $expensesPaid->whereNotNull('supplier_id')
            ->groupBy('supplier_id')
            ->map(fn($group) => [
                'supplier_name' => $group->first()->supplier?->name ?? '-',
                'total'         => $group->sum('amount'),
                'count'         => $group->count(),
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();

        // Breakdown biaya operasional (non-produk)
        $operasional = $expensesPaid->whereNull('product_id');
        $totalOperasional = $operasional->sum('amount');

        $laba = $totalIncome - $totalExpense;

        return [
            'month'              => $month,
            'month_label'        => $month->translatedFormat('F Y'),
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'ledgers'            => $ledgers,
            'total_income'       => $totalIncome,
            'total_expense'      => $totalExpense,
            'total_receivable'   => $totalReceivable,
            'total_payable'      => $totalPayable,
            'total_operasional'  => $totalOperasional,
            'laba'               => $laba,
            'incomes_paid'       => $incomesPaid,
            'expenses_paid'      => $expensesPaid,
            'receivables'        => $receivables,
            'payables'           => $payables,
            'sales_by_product'   => $salesByProduct,
            'purchase_by_supplier' => $purchaseBySupplier,
            'total_transactions' => $ledgers->count(),
        ];
    }

    /**
     * Generate PDF laporan harian.
     */
    public function generateDailyPdf(Carbon $date): string
    {
        $data = $this->getDailyData($date);
        $pdf  = Pdf::loadView('reports.daily', $data)
                   ->setPaper('a4', 'portrait');

        $filename = 'Laporan-Harian-' . $date->format('Y-m-d') . '.pdf';
        $path     = storage_path("app/reports/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }

    /**
     * Generate PDF laporan bulanan.
     */
    public function generateMonthlyPdf(Carbon $month): string
    {
        $data = $this->getMonthlyData($month);
        $pdf  = Pdf::loadView('reports.monthly', $data)
                   ->setPaper('a4', 'portrait');

        $filename = 'Laporan-Bulanan-' . $month->format('Y-m') . '.pdf';
        $path     = storage_path("app/reports/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }

    /**
     * Generate data laporan mingguan (Senin–Minggu).
     */
    public function getWeeklyData(Carbon $startDate, Carbon $endDate): array
    {
        $start = $startDate->toDateString();
        $end   = $endDate->toDateString();

        $ledgers = Ledger::with(['product', 'customer', 'supplier'])
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->orderBy('type')
            ->get();

        $incomesPaid   = $ledgers->where('type', 'income')->where('payment_status', 'paid');
        $totalIncome   = $incomesPaid->sum('amount');

        $expensesPaid  = $ledgers->where('type', 'expense')->where('payment_status', 'paid');
        $totalExpense  = $expensesPaid->sum('amount');

        $receivables     = $ledgers->where('type', 'income')->where('payment_status', 'unpaid');
        $totalReceivable = $receivables->sum('amount');

        $payables      = $ledgers->where('type', 'expense')->where('payment_status', 'unpaid');
        $totalPayable  = $payables->sum('amount');

        // Breakdown penjualan per produk
        $salesByProduct = $incomesPaid->whereNotNull('product_id')
            ->groupBy('product_id')
            ->map(fn($group) => [
                'product_name' => $group->first()->product?->name ?? '-',
                'qty'          => $group->sum('quantity'),
                'total'        => $group->sum('amount'),
            ])
            ->sortByDesc('total')
            ->values()
            ->toArray();

        // Breakdown biaya operasional
        $operasional = $expensesPaid->whereNull('product_id');
        $totalOperasional = $operasional->sum('amount');

        // Rekap per hari (untuk grafik mini)
        $dailyBreakdown = $ledgers->groupBy(fn($l) => $l->date->format('Y-m-d'))
            ->map(fn($group, $dateKey) => [
                'date'    => $dateKey,
                'day'     => Carbon::parse($dateKey)->translatedFormat('D, d/m'),
                'income'  => $group->where('type', 'income')->where('payment_status', 'paid')->sum('amount'),
                'expense' => $group->where('type', 'expense')->where('payment_status', 'paid')->sum('amount'),
            ])
            ->sortKeys()
            ->values()
            ->toArray();

        $laba = $totalIncome - $totalExpense;

        return [
            'start_date'         => $startDate,
            'end_date'           => $endDate,
            'period_label'       => $startDate->translatedFormat('d M') . ' — ' . $endDate->translatedFormat('d M Y'),
            'ledgers'            => $ledgers,
            'total_income'       => $totalIncome,
            'total_expense'      => $totalExpense,
            'total_receivable'   => $totalReceivable,
            'total_payable'      => $totalPayable,
            'total_operasional'  => $totalOperasional,
            'laba'               => $laba,
            'incomes_paid'       => $incomesPaid,
            'expenses_paid'      => $expensesPaid,
            'receivables'        => $receivables,
            'payables'           => $payables,
            'sales_by_product'   => $salesByProduct,
            'daily_breakdown'    => $dailyBreakdown,
            'total_transactions' => $ledgers->count(),
        ];
    }

    /**
     * Generate PDF laporan mingguan.
     */
    public function generateWeeklyPdf(Carbon $startDate, Carbon $endDate): string
    {
        $data = $this->getWeeklyData($startDate, $endDate);
        $pdf  = Pdf::loadView('reports.weekly', $data)
                   ->setPaper('a4', 'portrait');

        $filename = 'Laporan-Mingguan-' . $startDate->format('Y-m-d') . '_' . $endDate->format('Y-m-d') . '.pdf';
        $path     = storage_path("app/reports/{$filename}");

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $pdf->save($path);
        return $path;
    }
}
