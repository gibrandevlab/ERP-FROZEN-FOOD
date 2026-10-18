<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Models\TokenUsage;
use App\Services\DeepSeekService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatSessionController extends Controller
{
    /**
     * Memeriksa otorisasi pengguna (Khusus admin dan staf berwenang).
     */
    protected function authorizeChatAccess(Request $request): void
    {
        $user = $request->user();

        if (! $user) {
            abort(401, 'Akses ditolak: Silakan login terlebih dahulu.');
        }

        // Super-admin selalu diizinkan
        if ($user->is_admin) {
            return;
        }

        // Cek izin custom staf (canDo 'chat' atau 'dashboard')
        if (! $user->canDo('dashboard', 'view') && ! $user->canDo('chat', 'view')) {
            abort(403, 'Akses ditolak: Anda tidak memiliki hak akses ke fitur Asisten AI.');
        }
    }

    /**
     * GET /api/chat/sessions
     * Mengambil daftar riwayat sesi percakapan chat.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorizeChatAccess($request);

        $userId = $request->user()->id;

        $sessions = Conversation::where('user_id', $userId)
            ->withCount('messages')
            ->withSum('tokenUsages as total_tokens', 'total_tokens')
            ->withSum('tokenUsages as total_cache_hit_tokens', 'cache_hit_tokens')
            ->orderBy('updated_at', 'desc')
            ->paginate($request->integer('per_page', 20));

        $data = $sessions->map(function ($s) {
            $totalTokens = (int) ($s->total_tokens ?? 0);
            $cacheHitTokens = (int) ($s->total_cache_hit_tokens ?? 0);
            $cacheHitRatio = $totalTokens > 0 ? round(($cacheHitTokens / $totalTokens) * 100, 1) : 0;

            return [
                'session_id'       => $s->id,
                'id'               => $s->id,
                'title'            => $s->title,
                'messages_count'   => $s->messages_count,
                'total_tokens'     => $totalTokens,
                'cache_hit_tokens' => $cacheHitTokens,
                'cache_hit_ratio'  => $cacheHitRatio,
                'created_at'       => $s->created_at->toIso8601String(),
                'updated_at'       => $s->updated_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
            'pagination' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    /**
     * GET /api/chat/sessions/{id}
     * Memuat riwayat pesan dari sesi percakapan tertentu beserta rincian token usage.
     */
    public function show(Request $request, string $id): JsonResponse
    {
        $this->authorizeChatAccess($request);

        $session = Conversation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->with(['messages' => function ($q) {
                $q->with('tokenUsage')->orderBy('created_at', 'asc');
            }])
            ->firstOrFail();

        $messages = $session->messages->map(function ($msg) {
            $usage = null;
            if ($msg->tokenUsage) {
                $usage = [
                    'prompt_tokens'     => $msg->tokenUsage->prompt_tokens,
                    'completion_tokens' => $msg->tokenUsage->completion_tokens,
                    'total_tokens'      => $msg->tokenUsage->total_tokens,
                    'cache_hit_tokens'  => $msg->tokenUsage->cache_hit_tokens,
                    'cache_miss_tokens' => $msg->tokenUsage->cache_miss_tokens,
                    'cache_hit_ratio'   => $msg->tokenUsage->cache_hit_ratio,
                    'model'             => $msg->tokenUsage->model,
                ];
            }

            return [
                'id'          => $msg->id,
                'session_id'  => $msg->conversation_id,
                'role'        => $msg->role,
                'content'     => $msg->content,
                'token_usage' => $usage,
                'created_at'  => $msg->created_at->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => [
                'session'  => [
                    'id'               => $session->id,
                    'title'            => $session->title,
                    'total_tokens'     => $session->total_tokens,
                    'created_at'       => $session->created_at->toIso8601String(),
                    'updated_at'       => $session->updated_at->toIso8601String(),
                ],
                'messages' => $messages,
            ],
        ]);
    }

    /**
     * POST /api/chat/send
     * Mengirim pesan baru ke DeepSeek API dengan pemuatan konteks histori dan tracking token usage.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $this->authorizeChatAccess($request);

        // Cek kuota token AI pengguna
        $user = $request->user();
        if (! $user->canUseChatAI()) {
            return response()->json([
                'success' => false,
                'message' => $user->chatQuotaErrorMessage() ?: 'Kuota token AI Anda telah habis atau dinonaktifkan.',
            ], 403);
        }

        $validated = $request->validate([
            'message'    => 'required|string|max:5000',
            'session_id' => 'nullable|string',
        ]);

        $userText = trim($validated['message']);
        $sessionId = $validated['session_id'] ?? null;
        $userId = $user->id;

        DB::beginTransaction();
        try {
            // 1. Ambil sesi atau buat sesi baru jika belum ada
            if ($sessionId) {
                $session = Conversation::where('id', $sessionId)
                    ->where('user_id', $userId)
                    ->firstOrFail();
                $session->touch();
            } else {
                $title = Conversation::generateTitleFromContent($userText, 5);
                $session = Conversation::create([
                    'user_id' => $userId,
                    'title'   => $title,
                ]);
            }

            // 2. Simpan pesan User ke chat_messages
            $userMsg = ChatMessage::create([
                'conversation_id' => $session->id,
                'role'            => 'user',
                'content'         => $userText,
            ]);

            // 3. Buat placeholder pesan Assistant
            $assistantMsg = ChatMessage::create([
                'conversation_id' => $session->id,
                'role'            => 'assistant',
                'content'         => '',
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Chat API DB Error] ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pesan ke database: ' . $e->getMessage(),
            ], 500);
        }

        // 4. Kirim konteks pesan ke DeepSeek API (dengan truncation kontekstual)
        try {
            $replyResult = DeepSeekService::chat($session->id, $assistantMsg->id);
            $replyContent = $replyResult['content'] ?? '';
            $tokenUsage = $replyResult['token_usage'] ?? null;

            // Update isi pesan assistant
            $assistantMsg->update([
                'content' => $replyContent,
            ]);
            $session->touch();

            $tokenUsageData = null;
            if ($tokenUsage) {
                $tokenUsageData = [
                    'prompt_tokens'     => $tokenUsage->prompt_tokens,
                    'completion_tokens' => $tokenUsage->completion_tokens,
                    'total_tokens'      => $tokenUsage->total_tokens,
                    'cache_hit_tokens'  => $tokenUsage->cache_hit_tokens,
                    'cache_miss_tokens' => $tokenUsage->cache_miss_tokens,
                    'cache_hit_ratio'   => $tokenUsage->cache_hit_ratio,
                    'model'             => $tokenUsage->model,
                ];
            }

            return response()->json([
                'success'           => true,
                'session_id'        => $session->id,
                'user_message'      => [
                    'id'         => $userMsg->id,
                    'role'       => 'user',
                    'content'    => $userMsg->content,
                    'created_at' => $userMsg->created_at->toIso8601String(),
                ],
                'assistant_message' => [
                    'id'         => $assistantMsg->id,
                    'role'       => 'assistant',
                    'content'    => $replyContent,
                    'created_at' => $assistantMsg->created_at->toIso8601String(),
                ],
                'token_usage'       => $tokenUsageData,
            ]);

        } catch (\Throwable $e) {
            Log::error('[DeepSeek API Call Exception] ' . $e->getMessage());

            $assistantMsg->update([
                'content' => 'Maaf, terjadi kendala saat menghubungkan ke DeepSeek API: ' . $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'session_id' => $session->id,
                'message' => 'Terjadi kesalahan saat memproses jawaban AI: ' . $e->getMessage(),
                'assistant_message' => [
                    'id'         => $assistantMsg->id,
                    'role'       => 'assistant',
                    'content'    => $assistantMsg->content,
                    'created_at' => $assistantMsg->created_at->toIso8601String(),
                ],
            ], 500);
        }
    }

    /**
     * DELETE /api/chat/sessions/{id}
     * Menghapus sesi percakapan chat.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $this->authorizeChatAccess($request);

        $session = Conversation::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        $session->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesi percakapan berhasil dihapus.',
        ]);
    }

    /**
     * GET /api/chat/token-summary
     * Monitoring analitik penggunaan token & efisiensi cache untuk admin/pemilik bisnis.
     */
    public function tokenSummary(Request $request): JsonResponse
    {
        $this->authorizeChatAccess($request);

        $user = $request->user();

        $query = TokenUsage::query();
        if (! $user->is_admin) {
            // Non-admin hanya melihat token miliknya
            $query->whereHas('conversation', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $totalPromptTokens = (int) $query->sum('prompt_tokens');
        $totalCompletionTokens = (int) $query->sum('completion_tokens');
        $totalTokens = (int) $query->sum('total_tokens');
        $totalCacheHit = (int) $query->sum('cache_hit_tokens');
        $totalCacheMiss = (int) $query->sum('cache_miss_tokens');

        $overallCacheHitRatio = $totalPromptTokens > 0
            ? round(($totalCacheHit / $totalPromptTokens) * 100, 1)
            : 0;

        // Estimasi Biaya DeepSeek API (USD):
        // Cache Hit: $0.014 / 1M tokens
        // Cache Miss: $0.14 / 1M tokens
        // Output Tokens: $0.28 / 1M tokens
        $estimatedCostUsd = (($totalCacheHit * 0.014) + ($totalCacheMiss * 0.14) + ($totalCompletionTokens * 0.28)) / 1000000;
        $standardCostWithoutCache = (($totalPromptTokens * 0.14) + ($totalCompletionTokens * 0.28)) / 1000000;
        $savedCostUsd = max(0, $standardCostWithoutCache - $estimatedCostUsd);

        return response()->json([
            'success' => true,
            'data'    => [
                'total_requests'          => $query->count(),
                'total_tokens'            => $totalTokens,
                'total_prompt_tokens'     => $totalPromptTokens,
                'total_completion_tokens' => $totalCompletionTokens,
                'cache_hit_tokens'        => $totalCacheHit,
                'cache_miss_tokens'       => $totalCacheMiss,
                'cache_hit_ratio_percent' => $overallCacheHitRatio,
                'estimated_cost_usd'      => round($estimatedCostUsd, 4),
                'cost_saved_usd'          => round($savedCostUsd, 4),
            ],
        ]);
    }
}
