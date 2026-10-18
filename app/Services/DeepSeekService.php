<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\TokenUsage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    /**
     * Menyusun system prompt statis komprehensif yang berisi persona, aturan bisnis ERP Frozen Food,
     * dan skema database lengkap untuk memastikan panjangnya >1024 token (memaksimalkan DeepSeek Context Caching).
     */
    public static function getStaticSystemPrompt(): string
    {
        return <<<'PROMPT'
Kamu adalah "Riza AI Assistant" — Asisten Cerdas ERP & Business Intelligence untuk platform "ERP Frozen Food (Riza Frozen)".
Tugasmu adalah membantu staf, staf gudang, purchasing, finance/akuntan, dan pemilik bisnis dalam menganalisis stok inventaris, arus kas (cashflow), pengadaan barang (Purchase Request), evaluasi Supplier, serta keputusan SPK Restock.

================================================================================
ATURAN OUTPUT & FORMATTING (SANGAT KETAT)
================================================================================
1. GAYA BAHASA:
   - Bersikaplah profesional, to-the-point, analitis, jelas, dan ringkas.
   - Langsung jawab inti pertanyaan tanpa kalimat pembuka yang berlebihan.

2. DILARANG EMOJI OVERLOAD:
   - Gunakan emoji MAKSIMAL 1-2 buah saja untuk seluruh respon, atau TANPA emoji sama sekali jika tidak benar-benar diperlukan.
   - JANGAN PERNAH menyematkan emoji di setiap baris atau setiap poin daftar.

3. FORMATTING CLEAN & RAPI:
   - Jika membuat daftar/poin, SELALU gunakan baris baru (line break ganda / "\n\n") yang jelas antar poin.
   - Jangan menempelkan teks Markdown bold langsung berhimpitan di tengah kalimat tanpa pemisah baris yang rapi.
   - Gunakan Markdown tabel jika menyajikan lebih dari 3 baris data terstruktur.

4. JAWABAN SINGKAT & RELEVAN:
   - Untuk sapaan santai (seperti "hi", "halo", "p", "selamat pagi"), JAWAB LANGSUNG DENGAN SINGKAT dalam 1-2 kalimat.
     Contoh ideal: "Halo! Saya Asisten AI ERP Riza Frozen. Ada yang bisa saya bantu terkait stok, transaksi, atau laporan bisnis Anda hari ini?"
   - Untuk pertanyaan matematika, logika dasar, atau definisi umum yang tidak berkaitan dengan ERP (seperti "1+1 berapa", "akar 144", dll), JAWAB LANGSUNG solusinya dengan singkat.
     Contoh ideal: "1 + 1 = 2"
   - DILARANG KERAS menambahkan kalimat promosi atau penawaran bantuan fitur ERP secara berulang-ulang di akhir pesan jika pengguna hanya mengajukan pertanyaan singkat/salam.

5. AKSES KE DATABASE & EFISIENSI TOKEN KUERI:
   - Kamu MEMILIKI AKSES LANGSUNG ke database ERP melalui tool `query_database`.
   - Patuhi aturan efisiensi token berikut secara ketat:
     * DILARANG KERAS menggunakan `SELECT *`. Selalu cantumkan hanya kolom yang dibutuhkan (contoh: `SELECT name, price, min_stock`).
     * WAJIB GUNAKAN AGREGASI SQL (`SUM()`, `COUNT()`, `AVG()`, `MIN()`, `MAX()`, `GROUP BY`) jika pengguna meminta total omzet, jumlah transaksi, rata-rata, atau data statistik. JANGAN menarik ratusan baris data mentah ke chat!
     * Batasi daftar produk atau riwayat dengan `LIMIT 10` hingga `LIMIT 15`.
   - Tool akan mengembalikan data dalam format ringkas (`cols: ... \n rows: ...`). Baca dan rangkum data tersebut ke pengguna secara rapi dan elegan.

================================================================================
ARSITEKTUR & SKEMA DATABASE ERP FROZEN FOOD (STATIC SCHEMA CONTEXT)
================================================================================
Berikut adalah struktur skema database relasional lengkap sistem ini untuk referensi konteks kueri data:

1. Tabel `users`:
   - `id` (bigint, PK): ID unik user.
   - `name` (string): Nama lengkap staf / manajer.
   - `email` (string, unique): Alamat email untuk login.
   - `is_admin` (boolean): Flag super-admin (melewati semua gate permission).
   - `created_at`, `updated_at` (timestamps).
   - CATATAN: Kolom `password` dan `recovery_phrase` DILINDUNGI dan tidak boleh diakses.

2. Tabel `permissions` & `user_permissions`:
   - `permissions`: `id`, `name`, `key` ('products', 'ledger', 'categories', 'suppliers', 'customers', 'purchase_requests', 'spk', 'users').
   - `user_permissions`: `id`, `user_id`, `permission_id`, `can_view`, `can_create`, `can_edit`, `can_delete`.

3. Tabel `categories`:
   - `id` (bigint, PK): ID kategori.
   - `name` (string): Nama kategori ('Nugget & Sosis', 'Daging Sapi & Ayam', 'Seafood Frozen', 'Kentang & Sayur', 'Dimsum & Frozen Snack').
   - `slug` (string, unique): Slug URL kategori.
   - `description` (text, nullable): Deskripsi kategori.
   - `created_at`, `updated_at` (timestamps).

4. Tabel `products`:
   - `id` (bigint, PK): ID unik produk.
   - `category_id` (bigint, FK categories): Relasi kategori.
   - `user_id` (bigint, FK users): Creator produk.
   - `name` (string): Nama barang (contoh: 'Fiesta Chicken Nugget 500g', 'Daging Sapi Slice Sukiyaki 500g').
   - `slug` (string, unique): Slug nama produk.
   - `sku` (string, unique): Barcode / SKU produk inventaris.
   - `description` (text, nullable): Deskripsi detail produk & instruksi simpan.
   - `price` (decimal 15,2): Harga jual eceran normal.
   - `wholesale_price` (decimal 15,2, nullable): Harga grosir.
   - `wholesale_min_qty` (integer, default 0): Minimal pembelian grosir.
   - `cost` (decimal 15,2): Harga pokok pembelian (HPP/Cost per unit).
   - `unit` (string): Satuan unit ('pcs', 'pack', 'kg', 'dus', 'karton').
   - `image` (string, nullable): Path gambar produk.
   - `is_active` (boolean, default 1): Status aktif katalog.
   - `lead_time` (integer, default 2): Waktu tunggu pengiriman dari supplier (hari).
   - `min_stock` (integer, default 10): Batas safety stock minimum sebelum peringatan restock.
   - `created_at`, `updated_at`, `deleted_at` (timestamps, soft deletes).

5. Tabel `locations`:
   - `id` (bigint, PK): ID gudang / freezer.
   - `name` (string): Nama lokasi ('Cold Storage Utama -18°C', 'Freezer Display Toko', 'Gudang Transit').
   - `description` (text, nullable): Spesifikasi suhu atau catatan lokasi.
   - `created_at`, `updated_at` (timestamps).

6. Tabel `stocks`:
   - `id` (bigint, PK): ID record stok fisik.
   - `product_id` (bigint, FK products): Relasi produk.
   - `location_id` (bigint, FK locations): Relasi lokasi cold storage.
   - `quantity` (integer): Jumlah unit fisik tersedia di lokasi tersebut.
   - `created_at`, `updated_at` (timestamps).

7. Tabel `suppliers`:
   - `id` (bigint, PK): ID supplier.
   - `name` (string): Nama perusahaan distributor / supplier frozen food.
   - `phone` (string, nullable): Nomor WhatsApp / Telepon salesman.
   - `address` (text, nullable): Alamat gudang supplier.
   - `email` (string, nullable): Email supplier.
   - `created_at`, `updated_at` (timestamps).

8. Tabel `customers`:
   - `id` (bigint, PK): ID pelanggan.
   - `name` (string): Nama pelanggan.
   - `phone` (string, nullable): Nomor WhatsApp/telepon pelanggan.
   - `type` (enum 'seller', 'non_seller'): Tipe pembeli ('seller' = reseller/pedagang, 'non_seller' = eceran/umum).
   - `created_at`, `updated_at` (timestamps).
   - CATATAN PENTING: Tabel `customers` TIDAK MEMILIKI kolom `address`.

9. Tabel `ledgers` (Catatan Kas & Pembukuan Transaksi):
   - `id` (bigint, PK): ID transaksi.
   - `type` (enum 'income', 'expense'): PENTING: 'income' (pemasukan/omzet penjualan), 'expense' (pengeluaran/kulakan/operasional). JANGAN GUNAKAN 'in' atau 'out' pada kolom type ini!
   - `title` (string): Judul transaksi (contoh: 'Penjualan Nugget Ayam').
   - `slug` (string, unique): Slug ID transaksi.
   - `amount` (decimal 15,2): Nilai nominal uang dalam rupiah.
   - `payment_status` (enum 'paid', 'unpaid'): 'paid' (lunas), 'unpaid' (belum lunas / piutang pelanggan).
   - `due_date` (date, nullable): Tanggal jatuh tempo.
   - `date` (date): Tanggal aktual transaksi (CATATAN: nama kolomnya adalah `date`, BUKAN `transaction_date`).
   - `reference` (string, nullable): Nomor nota / invoice.
   - `product_id` (bigint, FK products, nullable): Relasi ID produk (WAJIB JOIN ke products p ON l.product_id = p.id untuk mencari produk terlaris).
   - `location_id` (bigint, FK locations, nullable): Lokasi cold storage.
   - `quantity` (integer, nullable): Jumlah pcs/unit barang terjual.
   - `stock_movement` (enum 'in', 'out', nullable): 'out' saat penjualan (stok berkurang), 'in' saat restock (stok bertambah).
   - `customer_id` (bigint, FK customers, nullable): Relasi pembeli.
   - `supplier_id` (bigint, FK suppliers, nullable): Relasi supplier.
   - `user_id` (bigint, FK users): Petugas admin/kasir.
   - `description` (text, nullable): Catatan tambahan.
   - `created_at`, `updated_at`, `deleted_at` (timestamps, soft deletes).

10. Tabel `purchase_requests` (Pengajuan Pembelian Stok):
    - `id` (bigint, PK): ID PR.
    - `code` (string, unique): Kode PR (contoh: PR-202610-001).
    - `product_id` (bigint, FK products): Produk yang diajukan.
    - `supplier_id` (bigint, FK suppliers, nullable): Target supplier.
    - `requested_by` (bigint, FK users): Staf pemohon.
    - `approved_by` (bigint, FK users, nullable): Manajer penyetuju.
    - `quantity` (integer): Jumlah unit pengajuan.
    - `estimated_cost` (decimal 15,2): Estimasi biaya (qty * cost).
    - `status` (enum 'draft', 'pending', 'approved', 'rejected', 'ordered', 'received').
    - `notes` (text, nullable): Alasan pengajuan restock.
    - `created_at`, `updated_at` (timestamps).

11. Tabel `conversations` & `chat_messages` (AI Memory Engine):
    - `conversations`: `id` (UUID PK), `user_id` (FK users), `title` (string), `created_at`, `updated_at`.
    - `chat_messages`: `id` (UUID PK), `conversation_id` (FK conversations), `role` (enum 'system', 'user', 'assistant', 'tool'), `content` (longText), `created_at`, `updated_at`.

================================================================================
ATURAN BISNIS & FORMULA SPK RESTOCK
================================================================================
- Formula Safety Stock = (Max Daily Sales * Max Lead Time) - (Avg Daily Sales * Avg Lead Time).
- Formula Reorder Point (ROP) = (Avg Daily Sales * Lead Time) + Safety Stock.
- Margin Keuntungan (%) = ((Harga Jual - Harga Pokok / HPP) / HPP) * 100%.
- Kriteria Peringatan Stok Kritis: Jika Total Fisik Stok di Seluruh Lokasi <= Product `min_stock` (default 10 unit).
- Prioritas Pembelian: Utamakan produk dengan perputaran cepat (Fast Moving) dan lead time supplier yang lebih panjang.
PROMPT;
    }

    /**
     * Membangun payload JSON untuk API DeepSeek menggunakan teknik Sliding Window Memory.
     *
     * @param string $conversationId ID Percakapan (UUID)
     * @param string|null $customSystemPrompt (Opsional) Override atau tambahan system prompt
     * @param bool $includeTools Sertakan tool definitions untuk Function Calling
     * @return array Payload siap serialize ke JSON untuk DeepSeek API
     */
    public static function buildDeepSeekPayload(string $conversationId, ?string $customSystemPrompt = null, bool $includeTools = false): array
    {
        $conversation = Conversation::find($conversationId);
        $maxMessages = (int) config('deepseek.max_context_messages', 10);
        $model = config('deepseek.model', 'deepseek-chat');
        $temperature = (float) config('deepseek.temperature', 0.7);

        // 1. Siapkan System Prompt Utama di urutan paling atas (Index 0)
        $systemContent = self::getStaticSystemPrompt();
        if ($customSystemPrompt) {
            $systemContent .= "\n\n" . trim($customSystemPrompt);
        }

        $messagesPayload = [
            [
                'role' => 'system',
                'content' => $systemContent,
            ],
        ];

        // 2. Ambil maksimal N pesan percakapan TERAKHIR dari database (Sliding Window)
        // Hanya ambil percakapan bersih (user & assistant final answer) agar konteks efisien dan valid
        if ($conversation) {
            $recentMessages = ChatMessage::where('conversation_id', $conversationId)
                ->where('role', '!=', 'system')
                ->where('role', '!=', 'tool')
                ->where('content', '!=', '[Tool Call Request]')
                ->where('content', '!=', '')
                ->latest('created_at')
                ->take($maxMessages)
                ->get()
                ->reverse()
                ->values();

            foreach ($recentMessages as $msg) {
                $messagesPayload[] = [
                    'role'    => $msg->role,
                    'content' => $msg->content,
                ];
            }
        }

        $payload = [
            'model'       => $model,
            'messages'    => $messagesPayload,
            'stream'      => false,
            'temperature' => $temperature,
            'max_tokens'  => (int) config('deepseek.max_tokens', 1200),
        ];

        // 3. Tambahkan tool definitions jika diminta (Function Calling)
        if ($includeTools) {
            $payload['tools'] = [DatabaseQueryTool::getToolDefinition()];
            $payload['tool_choice'] = 'auto';
        }

        return $payload;
    }

    /**
     * Memanggil DeepSeek API secara Real-time Streaming (Server-Sent Events).
     * Menerima callback $onChunk untuk setiap token/kata yang dialirkan ke frontend Livewire.
     *
     * @param string $conversationId UUID percakapan
     * @param callable $onChunk Callback fn(string $chunk)
     * @param string|null $assistantMessageId (Opsional) ID pesan ChatMessage untuk ditautkan ke token_usages
     * @return string Konten lengkap hasil streaming
     */
    public static function chatStream(string $conversationId, callable $onChunk, ?string $assistantMessageId = null): string
    {
        $apiKey = config('deepseek.api_key');
        $baseUrl = rtrim(config('deepseek.base_url', 'https://api.deepseek.com'), '/');
        $verifySsl = (bool) config('deepseek.verify_ssl', false);

        // Fallback jika API Key belum diisi: Stream mock response ramah
        if (empty($apiKey) || $apiKey === 'your_deepseek_api_key_here') {
            $fallbackText = "Halo! Saya Asisten AI ERP Riza Frozen.\n\n" .
                "Koneksi DeepSeek API saat ini belum dikonfigurasi. Silakan masukkan `DEEPSEEK_API_KEY` Anda di file `.env` untuk menghubungkan asisten ke database ERP secara langsung.";

            $words = preg_split('/(\s+)/u', $fallbackText, -1, PREG_SPLIT_DELIM_CAPTURE);
            $accumulated = '';
            foreach ($words as $w) {
                $accumulated .= $w;
                $onChunk($w);
                usleep(15000); // 15ms per chunk
            }
            return $accumulated;
        }

        try {
            // FASE 1: Tool Calling Loop (Non-stream pre-check)
            // Memeriksa apakah AI perlu menjalankan kueri database terlebih dahulu
            $payload = self::buildDeepSeekPayload($conversationId, null, true);
            $maxToolLoops = 5;

            for ($loop = 0; $loop < $maxToolLoops; $loop++) {
                $httpClient = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ])->timeout(60);

                if (! $verifySsl) {
                    $httpClient = $httpClient->withoutVerifying();
                }

                $response = $httpClient->post("{$baseUrl}/chat/completions", $payload);

                if ($response->failed()) {
                    $statusCode = $response->status();
                    $errorMsg = $response->json()['error']['message'] ?? $response->body();
                    $msg = "Terjadi kendala pada DeepSeek API [HTTP {$statusCode}]: {$errorMsg}";
                    $onChunk($msg);
                    return $msg;
                }

                $data = $response->json();
                $choice = $data['choices'][0] ?? null;

                if (! $choice) {
                    $msg = "Format respons dari DeepSeek API tidak valid.";
                    $onChunk($msg);
                    return $msg;
                }

                $finishReason = $choice['finish_reason'] ?? 'stop';
                $message = $choice['message'] ?? [];

                // Jika AI meminta tool call (misal: query_database)
                if ($finishReason === 'tool_calls' && ! empty($message['tool_calls'])) {
                    $toolCalls = $message['tool_calls'];

                    $payload['messages'][] = [
                        'role'       => 'assistant',
                        'content'    => $message['content'] ?? null,
                        'tool_calls' => $toolCalls,
                    ];

                    ChatMessage::create([
                        'conversation_id' => $conversationId,
                        'role'            => 'assistant',
                        'content'         => '[Tool Call Request]',
                        'tool_calls_json' => json_encode($toolCalls),
                    ]);

                    foreach ($toolCalls as $toolCall) {
                        $toolCallId = $toolCall['id'];
                        $functionName = $toolCall['function']['name'] ?? '';
                        $functionArgs = json_decode($toolCall['function']['arguments'] ?? '{}', true);

                        $toolResultContent = '';
                        if ($functionName === 'query_database') {
                            $sql = $functionArgs['sql'] ?? '';
                            $description = $functionArgs['description'] ?? 'Kueri database AI';

                            Log::info('[AI Tool Streaming] query_database', [
                                'conversation_id' => $conversationId,
                                'sql'             => $sql,
                            ]);

                            $queryResult = DatabaseQueryTool::executeReadOnly($sql, $description);
                            $toolResultContent = DatabaseQueryTool::formatResultForAI($queryResult);
                        } else {
                            $toolResultContent = json_encode([
                                'status'  => 'error',
                                'message' => "Tool '{$functionName}' tidak dikenal.",
                            ]);
                        }

                        ChatMessage::create([
                            'conversation_id' => $conversationId,
                            'role'            => 'tool',
                            'content'         => $toolResultContent,
                            'tool_call_id'    => $toolCallId,
                        ]);

                        $payload['messages'][] = [
                            'role'         => 'tool',
                            'tool_call_id' => $toolCallId,
                            'content'      => $toolResultContent,
                        ];
                    }

                    // Lanjut periksa respons berikutnya dari AI setelah eksekusi tool
                    continue;
                }

                // Jika AI sudah memberikan jawaban teks akhir (selesai mengeksekusi tool atau tanpa tool)
                $finalDirectContent = $message['content'] ?? '';
                if (! empty($finalDirectContent)) {
                    // Ekstraksi dan simpan token usage ke database
                    $usage = $data['usage'] ?? null;
                    if ($usage) {
                        $model = config('deepseek.model', 'deepseek-chat');
                        self::logTokenUsage($conversationId, $assistantMessageId, $usage, $model);
                    }

                    // Sanitasi teks jika ada tag DSML yang bocor
                    $finalDirectContent = preg_replace('/<[｜|]DSML[｜|].*?<\/[｜|]DSML[｜|]\s*calls>/s', '', $finalDirectContent);
                    $finalDirectContent = trim($finalDirectContent);

                    // STREAMING LOKAL HALUS: Mengeliminasi panggilan API kedua!
                    // Menghemat 50% token dan mencegah Cache Miss akibat modifikasi payload
                    $words = preg_split('/(\s+)/u', $finalDirectContent, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $accumulated = '';
                    foreach ($words as $w) {
                        $accumulated .= $w;
                        $onChunk($w);
                        usleep(10000); // 10ms per chunk untuk efek live streaming yang mulus
                    }

                    return $accumulated;
                }

                // Jika respons kosong, lanjut ke streaming fallback
                break;
            }

            // FASE 2: REAL-TIME STREAMING KE BROWSER (HANYA JIKA BELUM ADA KONTEN)
            // Tetap pertahankan struktur payload yang identik agar tidak memicu Cache Miss
            $streamPayload = $payload;
            $streamPayload['stream'] = true;

            $httpStream = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'text/event-stream',
            ]);

            if (! $verifySsl) {
                $httpStream = $httpStream->withoutVerifying();
            }

            $streamResponse = $httpStream->withOptions(['stream' => true])
                ->send('POST', "{$baseUrl}/chat/completions", [
                    'body' => json_encode($streamPayload),
                ]);

            if ($streamResponse->failed()) {
                $err = "Gagal memulai streaming dari DeepSeek [HTTP " . $streamResponse->status() . "].";
                $onChunk($err);
                return $err;
            }

            $stream = $streamResponse->toPsrResponse()->getBody();
            $buffer = '';
            $fullContent = '';

            while (! $stream->eof()) {
                $chunk = $stream->read(128);
                $buffer .= $chunk;

                while (($newlinePos = strpos($buffer, "\n")) !== false) {
                    $line = trim(substr($buffer, 0, $newlinePos));
                    $buffer = substr($buffer, $newlinePos + 1);

                    if (str_starts_with($line, 'data: ')) {
                        $dataStr = trim(substr($line, 6));
                        if ($dataStr === '[DONE]') {
                            break 2;
                        }

                        $json = json_decode($dataStr, true);
                        $delta = $json['choices'][0]['delta']['content'] ?? '';

                        if ($delta !== '') {
                            $fullContent .= $delta;
                            $onChunk($delta);
                        }
                    }
                }
            }

            // Sanitasi hasil respons: bersihkan tag DSML jika ada yang bocor dari DeepSeek
            $fullContent = preg_replace('/<[｜|]DSML[｜|].*?<\/[｜|]DSML[｜|]\s*calls>/s', '', $fullContent);
            $fullContent = trim($fullContent);

            return $fullContent;

        } catch (\Throwable $e) {
            Log::error('[DeepSeek Streaming Exception] ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $errMsg = "Terjadi kendala koneksi: " . $e->getMessage();
            $onChunk($errMsg);
            return $errMsg;
        }
    }

    /**
     * Memanggil DeepSeek API secara Synchronous (Non-streaming) dan mengembalikan respons beserta metadata token.
     *
     * @param string $conversationId UUID percakapan
     * @param string|null $assistantMessageId ID pesan ChatMessage assistant untuk ditautkan ke token_usages
     * @return array ['content' => string, 'token_usage' => TokenUsage|null]
     */
    public static function chat(string $conversationId, ?string $assistantMessageId = null): array
    {
        $accumulated = '';
        $accumulated = self::chatStream($conversationId, function (string $chunk) use (&$accumulated) {
            $accumulated .= $chunk;
        }, $assistantMessageId);

        $tokenUsage = $assistantMessageId
            ? TokenUsage::where('message_id', $assistantMessageId)->first()
            : TokenUsage::where('conversation_id', $conversationId)->latest()->first();

        return [
            'content'     => $accumulated,
            'token_usage' => $tokenUsage,
        ];
    }

    /**
     * Menyimpan rincian token usage dari objek response DeepSeek ke database.
     */
    public static function logTokenUsage(string $conversationId, ?string $messageId, ?array $usage, string $model = 'deepseek-chat'): ?TokenUsage
    {
        if (empty($usage)) {
            return null;
        }

        $promptTokens = (int) ($usage['prompt_tokens'] ?? 0);
        $completionTokens = (int) ($usage['completion_tokens'] ?? 0);
        $totalTokens = (int) ($usage['total_tokens'] ?? ($promptTokens + $completionTokens));
        $cacheHit = (int) ($usage['prompt_cache_hit_tokens'] ?? ($usage['prompt_tokens_details']['cached_tokens'] ?? 0));
        $cacheMiss = (int) ($usage['prompt_cache_miss_tokens'] ?? max(0, $promptTokens - $cacheHit));

        try {
            return TokenUsage::create([
                'conversation_id'   => $conversationId,
                'message_id'        => $messageId,
                'prompt_tokens'     => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens'      => $totalTokens,
                'cache_hit_tokens'  => $cacheHit,
                'cache_miss_tokens' => $cacheMiss,
                'model'             => $model,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[Token Usage Log Error] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Membangun payload JSON untuk keperluan debugging / inspeksi Sliding Window.
     */
    public static function buildInspectorPayload(string $conversationId): array
    {
        return self::buildDeepSeekPayload($conversationId, null, false);
    }
}
