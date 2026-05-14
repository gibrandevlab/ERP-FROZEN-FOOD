<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * SpkService — Sistem Pendukung Keputusan: Prioritas Restocking Produk
 *
 * Metode: Entropy (pembobotan objektif) + SAW (Simple Additive Weighting)
 *
 * Kriteria:
 *   C1 — Total Stok Saat Ini     → Cost   (stok rendah = lebih prioritas)
 *   C2 — Volume Penjualan         → Benefit (terjual banyak = lebih prioritas)
 *   C3 — Margin Keuntungan (%)    → Benefit (margin besar = lebih berharga)
 *   C4 — Perputaran Stok          → Benefit (perputaran cepat = lebih kritis)
 */
class SpkService
{
    // Tipe kriteria: 'benefit' atau 'cost'
    protected array $criteriaTypes = [
        'c1_stok'       => 'cost',
        'c2_terjual'    => 'benefit',
        'c3_margin'     => 'benefit',
        'c4_perputaran' => 'benefit',
    ];

    // Target hari stok yang ingin dicapai saat restock
    protected int $targetDays = 30;

    // Periode aktif (untuk hitung rata-rata harian)
    protected int $activeDays = 30;

    /**
     * Entry point utama: jalankan semua kalkulasi dan kembalikan hasil peringkat.
     *
     * @param  int $targetDays Target persediaan stok untuk X hari ke depan
     * @return array
     */
    public function run(int $targetDays = 30): array
    {
        // Simpan target hari dari user
        $this->targetDays = max(1, $targetDays);
        
        // Data riwayat penjualan selalu fix 30 hari terakhir agar relevan
        $this->activeDays = 30;

        // 1. Ambil data alternatif (produk) beserta nilai kriterianya
        $alternatives = $this->getAlternatives();

        if ($alternatives->isEmpty()) {
            return [
                'results'        => [],
                'weights'        => [],
                'entropy'        => [],
                'divergence'     => [],
                'total_products' => 0,
            ];
        }

        // 2. Bangun matriks keputusan: rows = produk, cols = kriteria
        $matrix   = $this->buildMatrix($alternatives);
        $criteria = array_keys($this->criteriaTypes);

        // 3. Hitung bobot Entropy
        $entropyData = $this->calculateEntropyWeights($matrix, $criteria);

        // 4. Normalisasi matriks dengan SAW
        $normalized = $this->normalizeMatrix($matrix, $criteria);

        // 5. Hitung skor akhir V_i
        $scores = $this->calculateScores($normalized, $entropyData['weights'], $criteria);

        // 6. Gabungkan data dan urutkan berdasarkan skor
        $results = $this->mergeResults($alternatives, $scores, $normalized, $entropyData['weights'], $criteria);

        return [
            'results'        => $results,
            'weights'        => $entropyData['weights'],
            'entropy'        => $entropyData['entropy'],
            'divergence'     => $entropyData['divergence'],
            'total_products' => count($results),
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 1: Ambil Data Alternatif dari Database
    // ─────────────────────────────────────────────────────────────────

    /**
     * Ambil semua produk aktif beserta nilai 4 kriteria SPK dari DB.
     */
    protected function getAlternatives(): Collection
    {
        // Gunakan riwayat 30 hari ke belakang secara fix
        $periodDays = 30;
        $dateFilter = now()->subDays($periodDays)->toDateString();

        return Product::query()
            ->where('is_active', true)
            ->with(['stocks', 'category'])
            ->get()
            ->map(function (Product $product) use ($dateFilter, $periodDays) {
                // C1 — Total stok semua lokasi
                $totalStok = $product->stocks->sum('quantity');

                // C2 — Volume penjualan (qty keluar dari ledger income)
                $terjualQuery = DB::table('ledgers')
                    ->where('product_id', $product->id)
                    ->where('type', 'income')
                    ->where('stock_movement', 'out')
                    ->whereNull('deleted_at');
                if ($dateFilter) {
                    $terjualQuery->where('date', '>=', $dateFilter);
                }
                $totalTerjual = (float) ($terjualQuery->sum('quantity') ?? 0);

                // C3 — Margin keuntungan (%)
                $margin = $product->cost > 0
                    ? round((($product->price - $product->cost) / $product->cost) * 100, 4)
                    : 0.0;

                // C4 — Perputaran stok (terjual / stok, hindari div/0)
                $perputaran = ($totalStok > 0 && $totalTerjual > 0)
                    ? round($totalTerjual / $totalStok, 4)
                    : 0.0;

                // Hitung rekomendasi beli:
                // rata-rata harian × target hari dari user − stok saat ini
                $dailyRate     = $totalTerjual / $periodDays;
                $targetStock   = ceil($dailyRate * $this->targetDays);
                $recommendedBuy = (int) max(0, $targetStock - $totalStok);
                
                // Hitung estimasi sisa hari dari stok saat ini
                $sisaHari = $dailyRate > 0 ? round($totalStok / $dailyRate, 1) : 999;

                return [
                    'id'              => $product->id,
                    'name'            => $product->name,
                    'sku'             => $product->sku,
                    'category'        => $product->category?->name ?? '—',
                    'unit'            => $product->unit,
                    'price'           => (float) $product->price,
                    'cost'            => (float) $product->cost,
                    // Nilai kriteria
                    'c1_stok'         => (float) max($totalStok, 0),
                    'c2_terjual'      => $totalTerjual,
                    'c3_margin'       => $margin,
                    'c4_perputaran'   => $perputaran,
                    // Rekomendasi praktis
                    'daily_rate'      => round($dailyRate, 2),
                    'target_stock'    => (int) $targetStock,
                    'recommended_buy' => $recommendedBuy,
                    'sisa_hari'       => $sisaHari,
                ];
            })
            // Filter produk yang punya setidaknya satu data tidak nol
            ->filter(fn ($p) =>
                $p['c1_stok'] + $p['c2_terjual'] + $p['c3_margin'] + $p['c4_perputaran'] > 0
            )
            ->values();
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 2: Bangun Matriks Keputusan
    // ─────────────────────────────────────────────────────────────────

    /**
     * Ubah Collection alternatif menjadi matriks 2D [produk_idx][kriteria_key].
     * Tambahkan epsilon kecil agar tidak ada nilai nol murni (needed for ln()).
     */
    protected function buildMatrix(Collection $alternatives): array
    {
        $epsilon = 0.0001; // konstanta kecil untuk menghindari ln(0)

        return $alternatives->map(fn ($a) => [
            'c1_stok'       => max($a['c1_stok'],       $epsilon),
            'c2_terjual'    => max($a['c2_terjual'],    $epsilon),
            'c3_margin'     => max($a['c3_margin'],     $epsilon),
            'c4_perputaran' => max($a['c4_perputaran'], $epsilon),
        ])->toArray();
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 3: Hitung Bobot Entropy
    // ─────────────────────────────────────────────────────────────────

    /**
     * Hitung bobot Entropy untuk setiap kriteria.
     *
     * Langkah:
     *   1) p_ij  = x_ij / Σ x_ij   (normalisasi per kolom)
     *   2) E_j   = -(1/ln(n)) * Σ [p_ij * ln(p_ij)]
     *   3) d_j   = 1 - E_j          (degree of divergence)
     *   4) w_j   = d_j / Σ d_j      (bobot ternormalisasi)
     */
    protected function calculateEntropyWeights(array $matrix, array $criteria): array
    {
        $n       = count($matrix); // jumlah alternatif
        $lnN     = log($n);       // ln(n) sebagai pembagi

        $entropy    = [];
        $divergence = [];
        $weights    = [];

        foreach ($criteria as $c) {
            // Jumlah kolom untuk normalisasi p_ij
            $colSum = array_sum(array_column($matrix, $c));
            if ($colSum == 0) $colSum = $n * 0.0001; // fallback aman

            // Hitung nilai entropy E_j
            $e = 0.0;
            foreach ($matrix as $row) {
                $p = $row[$c] / $colSum;
                if ($p > 0) {
                    $e += $p * log($p); // Σ p_ij * ln(p_ij)
                }
            }
            $e = ($lnN > 0) ? -($e / $lnN) : 0.0;
            $e = max(0.0, min(1.0, $e)); // clamp antara [0, 1]

            $entropy[$c]    = round($e, 6);
            $divergence[$c] = round(1.0 - $e, 6);
        }

        // Normalisasi bobot: w_j = d_j / Σ d_j
        $totalDivergence = array_sum($divergence);
        foreach ($criteria as $c) {
            $weights[$c] = ($totalDivergence > 0)
                ? round($divergence[$c] / $totalDivergence, 6)
                : round(1 / count($criteria), 6);
        }

        return [
            'entropy'    => $entropy,
            'divergence' => $divergence,
            'weights'    => $weights,
        ];
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 4: Normalisasi Matriks SAW
    // ─────────────────────────────────────────────────────────────────

    /**
     * Normalisasi nilai kriteria menggunakan rumus SAW:
     *   Benefit → r_ij = x_ij / max(x_j)
     *   Cost    → r_ij = min(x_j) / x_ij
     */
    protected function normalizeMatrix(array $matrix, array $criteria): array
    {
        // Hitung max dan min per kolom
        $maxValues = [];
        $minValues = [];
        foreach ($criteria as $c) {
            $col         = array_column($matrix, $c);
            $maxValues[$c] = max($col);
            $minValues[$c] = min($col);
        }

        $normalized = [];
        foreach ($matrix as $rowIdx => $row) {
            foreach ($criteria as $c) {
                $type = $this->criteriaTypes[$c];
                if ($type === 'benefit') {
                    $normalized[$rowIdx][$c] = $maxValues[$c] > 0
                        ? round($row[$c] / $maxValues[$c], 6)
                        : 0.0;
                } else { // cost
                    $normalized[$rowIdx][$c] = $row[$c] > 0
                        ? round($minValues[$c] / $row[$c], 6)
                        : 0.0;
                }
            }
        }

        return $normalized;
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 5: Hitung Skor Akhir V_i (SAW)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Hitung skor preferensi V_i = Σ (w_j * r_ij)
     */
    protected function calculateScores(array $normalized, array $weights, array $criteria): array
    {
        $scores = [];
        foreach ($normalized as $rowIdx => $row) {
            $vi = 0.0;
            foreach ($criteria as $c) {
                $vi += ($weights[$c] ?? 0) * ($row[$c] ?? 0);
            }
            $scores[$rowIdx] = round($vi, 6);
        }
        return $scores;
    }

    // ─────────────────────────────────────────────────────────────────
    // STEP 6: Gabungkan & Urutkan Hasil
    // ─────────────────────────────────────────────────────────────────

    /**
     * Gabungkan data alternatif, nilai normalisasi, dan skor V_i,
     * lalu urutkan dari skor tertinggi ke terendah.
     */
    protected function mergeResults(
        Collection $alternatives,
        array $scores,
        array $normalized,
        array $weights,
        array $criteria
    ): array {
        $results = [];

        foreach ($alternatives as $idx => $alt) {
            $vi       = $scores[$idx] ?? 0.0;
            $sisaHari = $alt['sisa_hari'] ?? 999;

            // Tentukan label prioritas berdasarkan REALITA sisa stok, BUKAN dari skor algoritma.
            // Skor algoritma (SAW) tetap digunakan untuk urutan peringkat (mana yang dikerjakan duluan).
            $priority = match (true) {
                $sisaHari <= 7  => 'kritis',      // Stok habis dalam <= 7 hari
                $sisaHari <= 14 => 'perhatian',   // Stok habis dalam <= 14 hari
                default         => 'aman',        // Stok lebih dari 14 hari
            };

            $normRow = $normalized[$idx] ?? [];

            $results[] = [
                'rank'            => 0, // diisi setelah sort
                'id'              => $alt['id'],
                'name'            => $alt['name'],
                'sku'             => $alt['sku'],
                'category'        => $alt['category'],
                'unit'            => $alt['unit'],
                'price'           => $alt['price'],
                // Nilai mentah
                'c1_stok'         => $alt['c1_stok'],
                'c2_terjual'      => $alt['c2_terjual'],
                'c3_margin'       => $alt['c3_margin'],
                'c4_perputaran'   => $alt['c4_perputaran'],
                // Nilai normalisasi (untuk keperluan akademis/debug)
                'r_c1'            => $normRow['c1_stok']       ?? 0,
                'r_c2'            => $normRow['c2_terjual']    ?? 0,
                'r_c3'            => $normRow['c3_margin']     ?? 0,
                'r_c4'            => $normRow['c4_perputaran'] ?? 0,
                // Skor akhir
                'score'           => $vi,
                'priority'        => $priority,
                // Rekomendasi praktis
                'daily_rate'      => $alt['daily_rate']      ?? 0,
                'target_stock'    => $alt['target_stock']    ?? 0,
                'recommended_buy' => $alt['recommended_buy'] ?? 0,
                'sisa_hari'       => $sisaHari,
            ];
        }

        // Urutkan skor tertinggi → terendah
        usort($results, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Tambahkan nomor ranking
        foreach ($results as $i => &$r) {
            $r['rank'] = $i + 1;
        }

        return $results;
    }
}
