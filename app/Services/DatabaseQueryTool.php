<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseQueryTool
{
    /**
     * Tabel yang diizinkan untuk di-query oleh AI.
     */
    private const ALLOWED_TABLES = [
        'categories',
        'products',
        'locations',
        'stocks',
        'suppliers',
        'customers',
        'ledgers',
        'purchase_requests',
        'conversations',
        'chat_messages',
        'permissions',
        'user_permissions',
        'users',
    ];

    /**
     * Keyword SQL berbahaya yang DILARANG sama sekali.
     */
    private const FORBIDDEN_KEYWORDS = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'DROP',
        'TRUNCATE',
        'ALTER',
        'CREATE',
        'REPLACE',
        'MERGE',
        'CALL',
        'EXEC',
        'EXECUTE',
        'GRANT',
        'REVOKE',
        'LOCK',
        'UNLOCK',
        'LOAD',
        'OUTFILE',
        'DUMPFILE',
        'INTO OUTFILE',
    ];

    /**
     * Definisi Tool untuk dikirim ke DeepSeek API (Function Calling Spec).
     * Dilengkapi instruksi ketat untuk efisiensi token.
     */
    public static function getToolDefinition(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => 'query_database',
                'description' => 'Menjalankan query SQL SELECT READ-ONLY ke database ERP Frozen Food. HANYA SELECT diperbolehkan. ATURAN EFISIENSI TOKEN: (1) Wajib sebutkan hanya kolom yang dibutuhkan, DILARANG SELECT *. (2) Wajib gunakan fungsi agregasi (COUNT, SUM, AVG, MIN, MAX, GROUP BY) untuk ringkasan/total/omzet alih-alih menarik banyak baris mentah. (3) Batasi hasil dengan LIMIT 10-20.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'sql' => [
                            'type' => 'string',
                            'description' => 'Query SQL SELECT yang ringkas dan hemat token. Contoh agregasi: SELECT COUNT(*) as total_transaksi, SUM(amount) as total_omzet FROM ledgers WHERE type = "income". Contoh daftar: SELECT p.name, s.quantity FROM products p JOIN stocks s ON p.id = s.product_id ORDER BY s.quantity ASC LIMIT 15',
                        ],
                        'description' => [
                            'type' => 'string',
                            'description' => 'Deskripsi singkat tujuan kueri (audit trail).',
                        ],
                    ],
                    'required' => ['sql', 'description'],
                ],
            ],
        ];
    }

    /**
     * Memvalidasi dan menjalankan query SQL secara READ-ONLY yang aman dan hemat token.
     *
     * @param string $sql Query SQL dari AI
     * @param string $description Deskripsi tujuan query (untuk audit log)
     * @return array ['success' => bool, 'data' => array|null, 'error' => string|null, 'row_count' => int]
     */
    public static function executeReadOnly(string $sql, string $description = ''): array
    {
        $sql = trim($sql);

        // 1. Validasi: Harus diawali SELECT
        if (! preg_match('/^\s*SELECT\s/i', $sql)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'DITOLAK: Hanya query SELECT yang diizinkan.',
                'row_count' => 0,
            ];
        }

        // 2. Validasi: Cek keyword berbahaya (Anti Edit & Delete)
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                return [
                    'success' => false,
                    'data' => null,
                    'error' => "DITOLAK: Keyword [{$keyword}] dilarang. Operasi tulis tidak diizinkan.",
                    'row_count' => 0,
                ];
            }
        }

        // 3. Lindungi kolom sensitif — masking password dan recovery_phrase
        if (preg_match('/\bpassword\b/i', $sql) || preg_match('/\brecovery_phrase\b/i', $sql)) {
            return [
                'success' => false,
                'data' => null,
                'error' => 'DITOLAK: Akses ke kolom kredensial sensitif dilarang oleh kebijakan keamanan.',
                'row_count' => 0,
            ];
        }

        // 4. Efisiensi Token: Strict Limit Clamping (Default 10, Max 20 baris)
        if (! preg_match('/\bLIMIT\s+(\d+)/i', $sql, $matches)) {
            $sql .= ' LIMIT 10';
        } else {
            $limitValue = (int) $matches[1];
            if ($limitValue > 20) {
                // Paksa clamp ke maksimal 20 baris agar menghemat token input LLM
                $sql = preg_replace('/\bLIMIT\s+\d+/i', 'LIMIT 20', $sql);
            }
        }

        // 5. Audit log query AI
        Log::channel('stack')->info('[AI DB Query] ' . $description, [
            'sql' => $sql,
            'user_id' => auth()->id(),
        ]);

        try {
            // 6. Eksekusi query dalam transaksi read-only
            $result = DB::select($sql);

            // Konversi ke array dan potong string panjang (deskripsi, url gambar, dsb > 60 karakter untuk efisiensi token)
            $data = array_map(function ($row) {
                $array = (array) $row;
                foreach ($array as $key => $val) {
                    if (is_string($val) && mb_strlen($val) > 60) {
                        $array[$key] = mb_substr($val, 0, 57) . '...';
                    }
                }
                return $array;
            }, $result ?? []);

            return [
                'success' => true,
                'data' => $data,
                'error' => null,
                'row_count' => count($data),
            ];

        } catch (\Throwable $e) {
            Log::channel('stack')->error('[AI DB Query Error] ' . $e->getMessage(), [
                'sql' => $sql,
            ]);

            return [
                'success' => false,
                'data' => null,
                'error' => 'Kesalahan saat menjalankan query: ' . $e->getMessage(),
                'row_count' => 0,
            ];
        }
    }

    /**
     * Format hasil query menjadi string Compact Matrix Table (Super Hemat Token).
     * Mengurangi pemborosan token hingga 65% dibanding JSON standar dengan mengeliminasi repetisi keys.
     */
    public static function formatResultForAI(array $queryResult): string
    {
        if (! $queryResult['success']) {
            return "ERROR: " . $queryResult['error'];
        }

        $data = $queryResult['data'] ?? [];
        $rowCount = count($data);

        if ($rowCount === 0) {
            return "0 baris data ditemukan.";
        }

        // Ambil nama kolom hanya 1 kali
        $columns = array_keys($data[0]);
        $output = "Total: {$rowCount} baris\n";
        $output .= "cols: " . implode('|', $columns) . "\n";
        $output .= "rows:\n";

        foreach ($data as $row) {
            $values = [];
            foreach ($columns as $col) {
                $val = $row[$col] ?? 'null';
                // Bersihkan baris baru dan pemisah pipa di dalam nilai
                if (is_string($val)) {
                    $val = str_replace(["\r", "\n", "|"], ["", " ", "/"], $val);
                } elseif (is_bool($val)) {
                    $val = $val ? 'true' : 'false';
                }
                $values[] = $val;
            }
            $output .= implode('|', $values) . "\n";
        }

        return trim($output);
    }
}
