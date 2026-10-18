<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseRequest;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * ForecastService — Prakiraan permintaan & reorder point produk.
 *
 * Metode:
 *   - Ensemble rata-rata historis (50%), Moving Average 30 hari (30%),
 *     dan Single Exponential Smoothing (20%) untuk laju permintaan harian
 *   - Standar deviasi historis untuk safety stock
 *   - Reorder Point (ROP) = laju harian x lead time + safety stock
 *   - Saran beli = kebutuhan selama (lead time + horizon) - stok saat ini
 *
 * Data penjualan diambil dari ledgers (type income, stock_movement out).
 */
class ForecastService
{
    /** Alpha smoothing (0 < alpha < 1); makin besar makin responsif ke data terbaru. */
    protected float $alpha = 0.35;

    /** Service level 95% -> z = 1.65 (asumsi distribusi normal). */
    protected float $zScore = 1.65;

    /**
     * Jalankan peramalan untuk seluruh produk aktif.
     *
     * @param  int $horizonDays  Jumlah hari yang ingin dipenuhi setelah barang datang
     * @param  int $historyDays  Panjang riwayat penjualan yang dipakai (hari)
     */
    public function run(int $horizonDays = 30, int $historyDays = 90): array
    {
        $horizon = min(365, max(1, $horizonDays));
        $history = min(365, max(14, $historyDays));

        $today = now('Asia/Jakarta')->startOfDay();
        $start = $today->copy()->subDays($history - 1);
        $startDate = $start->toDateString();
        $endDate   = $today->toDateString();

        $products = Product::query()
            ->where('is_active', true)
            ->with(['stocks', 'category'])
            ->orderBy('name')
            ->get();

        // Agregasi penjualan per produk per tanggal (1 query untuk semua produk).
        $salesRows = DB::table('ledgers')
            ->selectRaw('product_id, DATE(date) as sale_date, SUM(quantity) as qty')
            ->where('type', 'income')
            ->where('stock_movement', 'out')
            ->whereNotNull('product_id')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$startDate, $endDate])
            ->groupBy('product_id', DB::raw('DATE(date)'))
            ->get();

        $salesByProduct = $salesRows
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->pluck('qty', 'sale_date'));

        // Daftar tanggal lengkap agar hari tanpa penjualan tetap dihitung sebagai 0.
        $dates = [];
        for ($cursor = $start->copy(); $cursor->lte($today); $cursor->addDay()) {
            $dates[] = $cursor->toDateString();
        }

        $results = [];

        foreach ($products as $product) {
            $saleMap = $salesByProduct->get($product->id, collect());

            $series = [];
            foreach ($dates as $date) {
                $series[] = (float) ($saleMap[$date] ?? 0);
            }

            $stock = max(0, (int) $product->stocks->sum('quantity'));
            $leadTime = max(1, (int) ($product->lead_time ?? 0));
            $minStock = max(0, (int) ($product->min_stock ?? 0));
            $cost = (float) ($product->cost ?? 0);

            $statistics = $this->calculateStatistics($series);
            $dailyRate = $statistics['daily_rate'];

            $safetyStock = $this->zScore * $statistics['std_dev'] * sqrt($leadTime);
            $reorderPoint = ($dailyRate * $leadTime) + $safetyStock;
            $daysRemaining = $dailyRate > 0 ? $stock / $dailyRate : null;

            $recommendedQty = $this->recommendedQuantity(
                $dailyRate,
                $horizon,
                $leadTime,
                $safetyStock,
                $stock,
                $minStock
            );

            $status = $this->determineStatus(
                $dailyRate,
                $stock,
                $minStock,
                $daysRemaining,
                $leadTime,
                $reorderPoint,
                $stock - ($dailyRate * $horizon)
            );

            $results[] = [
                'id'                => $product->id,
                'name'              => $product->name,
                'sku'               => $product->sku,
                'category'          => $product->category?->name ?? '—',
                'unit'              => $product->unit,
                'cost'              => $cost,
                'lead_time'         => (int) ($product->lead_time ?? 0),
                'lead_time_used'    => $leadTime,
                'min_stock'         => $minStock,
                'stock'             => $stock,
                'total_sold'        => (int) array_sum($series),
                'daily_rate'        => round($dailyRate, 3),
                'ma_7'              => round($statistics['ma_7'], 3),
                'ma_30'             => round($statistics['ma_30'], 3),
                'std_dev'           => round($statistics['std_dev'], 3),
                'mape'              => $statistics['mape'],
                'safety_stock'      => (int) ceil($safetyStock),
                'reorder_point'     => (int) ceil($reorderPoint),
                'days_remaining'    => $daysRemaining === null ? null : round($daysRemaining, 1),
                'forecast_demand'   => (int) ceil($dailyRate * $horizon),
                'projected_stock'   => (int) round($stock - ($dailyRate * $horizon)),
                'recommended_qty'   => (int) $recommendedQty,
                'estimated_cost'    => round($recommendedQty * $cost, 2),
                'status'            => $status,
            ];
        }

        usort($results, function (array $a, array $b): int {
            $priority = ['kritis' => 0, 'perhatian' => 1, 'aman' => 2, 'tanpa_penjualan' => 3];
            $order = ($priority[$a['status']] ?? 9) <=> ($priority[$b['status']] ?? 9);

            if ($order !== 0) {
                return $order;
            }

            return ($a['days_remaining'] ?? PHP_INT_MAX) <=> ($b['days_remaining'] ?? PHP_INT_MAX);
        });

        return [
            'results'          => $results,
            'summary'          => $this->summarize($results, $horizon, $history),
            'horizon_days'     => $horizon,
            'history_days'     => $history,
            'alpha'            => $this->alpha,
            'service_level'    => 95,
            'z_score'          => $this->zScore,
            'generated_at'     => now('Asia/Jakarta'),
        ];
    }

    /**
     * Buat draft Purchase Request (status pending) dari produk terpilih.
     *
     * PR tidak langsung disetujui. Draft masuk ke modul Persetujuan Pembelian
     * dan harus disetujui lewat RBAC purchase_approval.
     *
     * @return array{created:int, skipped:int, tickets:array<int,string>}
     */
    public function createDraftRequests(
        array $productIds,
        int $locationId,
        ?int $supplierId = null,
        int $horizonDays = 30,
        int $historyDays = 90
    ): array {
        $productIds = array_values(array_unique(array_map('intval', $productIds)));

        if (empty($productIds)) {
            throw ValidationException::withMessages([
                'selected' => 'Pilih minimal satu produk yang perlu direstock.',
            ]);
        }

        $location = Location::where('is_active', true)->find($locationId);
        if (! $location) {
            throw ValidationException::withMessages([
                'location_id' => 'Lokasi gudang tidak valid atau tidak aktif.',
            ]);
        }

        if ($supplierId !== null) {
            $supplier = Supplier::where('is_active', true)->whereNull('deleted_at')->find($supplierId);
            if (! $supplier) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Supplier tidak valid atau tidak aktif.',
                ]);
            }
        }

        $forecast = $this->run($horizonDays, $historyDays);
        $rows = collect($forecast['results'])->keyBy('id');

        $created = [];
        $skipped = 0;

        DB::transaction(function () use ($productIds, $locationId, $supplierId, $rows, $forecast, &$created, &$skipped): void {
            foreach ($productIds as $productId) {
                $row = $rows->get($productId);

                if (! $row || $row['recommended_qty'] <= 0) {
                    $skipped++;
                    continue;
                }

                // Hindari draft ganda untuk produk + lokasi yang sama saat masih pending.
                $alreadyPending = PurchaseRequest::where('product_id', $productId)
                    ->where('location_id', $locationId)
                    ->where('status', 'pending')
                    ->exists();

                if ($alreadyPending) {
                    $skipped++;
                    continue;
                }

                do {
                    $ticketNumber = 'PR-' . now('Asia/Jakarta')->format('Ymd') . '-' . Str::upper(Str::random(8));
                } while (PurchaseRequest::where('ticket_number', $ticketNumber)->exists());

                PurchaseRequest::create([
                    'ticket_number'  => $ticketNumber,
                    'product_id'     => $productId,
                    'location_id'    => $locationId,
                    'supplier_id'    => $supplierId,
                    'requested_qty'  => $row['recommended_qty'],
                    'status'         => 'pending',
                    'requested_by'   => auth()->id(),
                    'request_notes'  => sprintf(
                        'Draft Forecast Restock: proyeksi %d hari, laju %.2f %s/hari, ROP %d, sisa stok %d, safety stock %d.',
                        $forecast['horizon_days'],
                        $row['daily_rate'],
                        $row['unit'],
                        $row['reorder_point'],
                        $row['stock'],
                        $row['safety_stock']
                    ),
                ]);

                $created[] = $ticketNumber;
            }
        });

        return [
            'created' => count($created),
            'skipped' => $skipped,
            'tickets' => $created,
        ];
    }

    /**
     * Hitung statistik deret harian: SES, MA7, MA30, standar deviasi, dan MAPE.
     *
     * @param  array<int, float> $series
     * @return array{daily_rate:float, ma_7:float, ma_30:float, std_dev:float, mape:?float}
     */
    protected function calculateStatistics(array $series): array
    {
        $count = count($series);
        $mean  = $count > 0 ? array_sum($series) / $count : 0.0;

        $varianceSum = 0.0;
        foreach ($series as $value) {
            $varianceSum += ($value - $mean) ** 2;
        }
        $stdDev = $count > 1 ? sqrt($varianceSum / ($count - 1)) : 0.0;

        $ma7  = $this->averageOfLast($series, 7);
        $ma30 = $this->averageOfLast($series, 30);

        // SES: level awal = rata-rata 7 hari pertama agar tidak bias nol.
        $warmup = min(7, $count);
        $level = $warmup > 0 ? array_sum(array_slice($series, 0, $warmup)) / $warmup : 0.0;

        $errors = [];
        $runningSum = 0.0;

        foreach ($series as $index => $actual) {
            if ($index >= $warmup) {
                $priorCount = $index;
                $longMean = $priorCount > 0 ? $runningSum / $priorCount : 0.0;
                $window = array_slice($series, max(0, $index - 30), min(30, $index));
                $ma30Prior = count($window) > 0 ? array_sum($window) / count($window) : 0.0;

                // Ensemble: stabil untuk permintaan intermiten, tetap responsif ke tren.
                $forecast = (0.5 * $longMean) + (0.3 * $ma30Prior) + (0.2 * $level);

                if ($actual > 0) {
                    $errors[] = abs($actual - $forecast) / $actual;
                }
            }

            $runningSum += $actual;
            $level = ($this->alpha * $actual) + ((1 - $this->alpha) * $level);
        }

        $dailyRate = (0.5 * $mean) + (0.3 * $ma30) + (0.2 * max(0.0, $level));

        if ($dailyRate <= 0.01 && $count > 0) {
            $dailyRate = $mean;
        }

        $mape = count($errors) > 0 ? round((array_sum($errors) / count($errors)) * 100, 2) : null;

        return [
            'daily_rate' => max(0.0, $dailyRate),
            'ma_7'       => $ma7,
            'ma_30'      => $ma30,
            'std_dev'    => $stdDev,
            'mape'       => $mape,
        ];
    }

    /**
     * Rata-rata N data terakhir.
     */
    protected function averageOfLast(array $series, int $window): float
    {
        if (empty($series)) {
            return 0.0;
        }

        $slice = array_slice($series, -1 * min($window, count($series)));

        return array_sum($slice) / count($slice);
    }

    /**
     * Hitung jumlah beli yang disarankan.
     */
    protected function recommendedQuantity(
        float $dailyRate,
        int $horizonDays,
        int $leadTime,
        float $safetyStock,
        int $stock,
        int $minStock
    ): int {
        if ($dailyRate <= 0.01) {
            return max(0, $minStock - $stock);
        }

        $target = ($dailyRate * ($horizonDays + $leadTime)) + $safetyStock;

        return (int) max(0, ceil($target - $stock));
    }

    /**
     * Tentukan status prioritas restock.
     */
    protected function determineStatus(
        float $dailyRate,
        int $stock,
        int $minStock,
        ?float $daysRemaining,
        int $leadTime,
        float $reorderPoint,
        float $projectedStock
    ): string {
        if ($dailyRate > 0.01 && $daysRemaining !== null && $daysRemaining <= $leadTime) {
            return 'kritis';
        }

        if ($dailyRate > 0.01 && (
            $stock <= $reorderPoint
            || ($daysRemaining !== null && $daysRemaining <= $leadTime + 7)
            || $projectedStock < 0
        )) {
            return 'perhatian';
        }

        if ($dailyRate <= 0.01 && $stock < $minStock) {
            return 'perhatian';
        }

        if ($dailyRate <= 0.01) {
            return 'tanpa_penjualan';
        }

        return 'aman';
    }

    /**
     * Ringkasan untuk kartu statistik halaman.
     */
    protected function summarize(array $results, int $horizonDays, int $historyDays): array
    {
        $countByStatus = ['kritis' => 0, 'perhatian' => 0, 'aman' => 0, 'tanpa_penjualan' => 0];
        $totalQty = 0;
        $totalCost = 0.0;
        $mapeValues = [];

        foreach ($results as $row) {
            $countByStatus[$row['status']] = ($countByStatus[$row['status']] ?? 0) + 1;
            $totalQty += $row['recommended_qty'];
            $totalCost += $row['estimated_cost'];

            if ($row['mape'] !== null) {
                $mapeValues[] = $row['mape'];
            }
        }

        return [
            'total_products'        => count($results),
            'kritis'                => $countByStatus['kritis'],
            'perhatian'             => $countByStatus['perhatian'],
            'aman'                  => $countByStatus['aman'],
            'tanpa_penjualan'       => $countByStatus['tanpa_penjualan'],
            'total_recommended_qty' => $totalQty,
            'estimated_cost'        => round($totalCost, 2),
            'average_mape'          => count($mapeValues) > 0 ? round(array_sum($mapeValues) / count($mapeValues), 2) : null,
            'horizon_days'          => $horizonDays,
            'history_days'          => $historyDays,
        ];
    }
}
