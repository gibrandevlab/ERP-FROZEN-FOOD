<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\DeepSeekService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public ?string $activeConversationId = null;
    public string $messageInput = '';
    public bool $sidebarOpen = true;

    // State untuk Rename
    public ?string $editingConversationId = null;
    public string $editingTitle = '';

    // State untuk Modal & Konfirmasi Hapus
    public ?string $conversationToDelete = null;
    public bool $showDeleteModal = false;
    public bool $showPayloadModal = false;

    // State untuk Token Stats Panel di Sidebar
    public bool $showTokenStats = false;

    // State untuk Real-time Streaming
    public ?string $streamingMessageId = null;

    public function mount(?string $conversation = null): void
    {
        if ($conversation) {
            $conv = Conversation::forUser(auth()->id())->find($conversation);
            if ($conv) {
                $this->activeConversationId = $conv->id;
            } else {
                // Sesi bukan milik user ini atau tidak ditemukan -> redirect bersih ke chat baru
                $this->redirect(route('chat.index'), navigate: true);
            }
        }
    }

    /**
     * Mengambil daftar percakapan user yang dikelompokkan berdasarkan waktu.
     */
    #[Computed]
    public function groupedConversations()
    {
        $conversations = Conversation::forUser(auth()->id())->get();

        return $conversations->groupBy('time_group');
    }

    /**
     * Mengambil percakapan aktif beserta seluruh relasi pesan milik user yang sedang login.
     */
    #[Computed]
    public function activeConversation()
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return Conversation::with(['messages' => function ($q) {
            $q->with('tokenUsage')->orderBy('created_at', 'asc');
        }, 'tokenUsages'])->forUser(auth()->id())->find($this->activeConversationId);
    }

    /**
     * Statistik penggunaan token untuk sesi aktif (ditampilkan di sidebar - hanya admin).
     */
    #[Computed]
    public function tokenStats(): array
    {
        $activeConv = $this->activeConversation;
        if (! $activeConv) {
            return [];
        }

        $usages = $activeConv->tokenUsages;

        if ($usages->isEmpty()) {
            return [];
        }

        $totalPrompt     = $usages->sum('prompt_tokens');
        $totalCompletion = $usages->sum('completion_tokens');
        $totalTokens     = $usages->sum('total_tokens');
        $cacheHit        = $usages->sum('cache_hit_tokens');
        $cacheMiss       = $usages->sum('cache_miss_tokens');
        $hitRatio        = $totalPrompt > 0 ? round(($cacheHit / max($totalPrompt, 1)) * 100, 1) : 0;
        // DeepSeek pricing: cache hit $0.014/1M, cache miss $0.14/1M, output $0.28/1M
        $costUsd         = (($cacheMiss * 0.14) + ($cacheHit * 0.014) + ($totalCompletion * 0.28)) / 1_000_000;
        $savedUsd        = ($cacheHit * (0.14 - 0.014)) / 1_000_000; // saving vs no-cache price

        return compact('totalTokens', 'totalPrompt', 'totalCompletion', 'cacheHit', 'cacheMiss', 'hitRatio', 'costUsd', 'savedUsd');
    }

    /**
     * Mengelompokkan pesan dan proses berpikir / kueri database (agar bisa di-hide/unhide).
     */
    #[Computed]
    public function messageGroups(): array
    {
        $messages = $this->activeConversation?->messages ?? collect();
        $groups = [];
        $currentProcess = [];

        foreach ($messages as $msg) {
            if ($msg->isUser()) {
                if (! empty($currentProcess)) {
                    $groups[] = ['type' => 'process', 'items' => $currentProcess];
                    $currentProcess = [];
                }
                $groups[] = ['type' => 'message', 'message' => $msg];
            } elseif ($msg->role === 'tool' || ($msg->role === 'assistant' && $msg->tool_calls_json)) {
                $currentProcess[] = $msg;
            } else {
                if (! empty($currentProcess)) {
                    $groups[] = ['type' => 'process', 'items' => $currentProcess];
                    $currentProcess = [];
                }
                $groups[] = ['type' => 'message', 'message' => $msg];
            }
        }

        if (! empty($currentProcess)) {
            $groups[] = ['type' => 'process', 'items' => $currentProcess];
        }

        return $groups;
    }

    /**
     * Memulai sesi percakapan baru (+).
     */
    public function newConversation(): void
    {
        $this->activeConversationId = null;
        $this->messageInput = '';
        $this->streamingMessageId = null;
        $this->cancelRename();
        $this->dispatch('focus-input');
    }

    /**
     * Berpindah ke sesi percakapan yang dipilih.
     */
    public function selectConversation(string $id): void
    {
        $conv = Conversation::forUser(auth()->id())->findOrFail($id);
        $this->activeConversationId = $conv->id;
        $this->cancelRename();
        $this->dispatch('scroll-to-bottom');
        $this->dispatch('focus-input');
    }

    /**
     * Mengirim pesan baru ke sesi percakapan dengan Real-time Streaming (wire:stream).
     */
    public function sendMessage(): void
    {
        $userText = trim($this->messageInput);
        if (empty($userText)) {
            return;
        }

        // ── Cek kuota token AI sebelum apapun ──────────────────────────────
        $user = auth()->user();
        if (! $user->canUseChatAI()) {
            $this->dispatch('notify', type: 'error', message: $user->chatQuotaErrorMessage() ?: 'Kuota token AI Anda telah habis.');
            return;
        }

        $userId = auth()->id();

        // 1. Buat percakapan jika belum ada sesi aktif
        if (! $this->activeConversationId) {
            $title = Conversation::generateTitleFromContent($userText, 5);
            $conversation = Conversation::create([
                'user_id' => $userId,
                'title'   => $title,
            ]);
            $this->activeConversationId = $conversation->id;
        } else {
            $conversation = Conversation::forUser($userId)->findOrFail($this->activeConversationId);
            $conversation->touch();
        }

        // 2. Simpan pesan User ke database
        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userText,
        ]);

        $this->messageInput = '';
        $this->dispatch('scroll-to-bottom');

        // 3. Buat placeholder pesan Assistant untuk Real-time Streaming
        $assistantMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => '',
        ]);

        $this->streamingMessageId = $assistantMsg->id;
        $streamTarget = 'stream-msg-' . $assistantMsg->id;

        // 4. Panggil DeepSeek Streaming API (Server-Sent Events)
        $fullReply = DeepSeekService::chatStream($conversation->id, function (string $chunk) use ($streamTarget) {
            $this->stream(to: $streamTarget, content: $chunk, replace: false);
            $this->dispatch('scroll-to-bottom');
        }, $assistantMsg->id);

        // 5. Simpan isi lengkap pesan Assistant ke database
        $assistantMsg->update([
            'content' => $fullReply,
        ]);

        $this->streamingMessageId = null;
        $conversation->touch();
        $this->dispatch('scroll-to-bottom');
    }

    /**
     * Kirim pesan dari kartu saran (quick prompts).
     */
    public function sendSuggestedPrompt(string $prompt): void
    {
        $this->messageInput = $prompt;
        $this->sendMessage();
    }

    /**
     * Memulai proses Rename judul percakapan.
     */
    public function startRename(string $id): void
    {
        $conv = Conversation::forUser(auth()->id())->find($id);
        if ($conv) {
            $this->editingConversationId = $id;
            $this->editingTitle = $conv->title;
        }
    }

    /**
     * Menyimpan perubahan judul percakapan.
     */
    public function saveRename(): void
    {
        if (! $this->editingConversationId) {
            return;
        }

        $cleanTitle = trim($this->editingTitle);
        if (! empty($cleanTitle)) {
            $conv = Conversation::forUser(auth()->id())->find($this->editingConversationId);
            if ($conv) {
                $conv->update(['title' => Str::limit($cleanTitle, 80)]);
            }
        }

        $this->cancelRename();
    }

    /**
     * Membatalkan proses Rename.
     */
    public function cancelRename(): void
    {
        $this->editingConversationId = null;
        $this->editingTitle = '';
    }

    /**
     * Membuka modal konfirmasi hapus percakapan.
     */
    public function confirmDelete(string $id): void
    {
        $this->conversationToDelete = $id;
        $this->showDeleteModal = true;
    }

    /**
     * Menghapus percakapan dari database.
     */
    public function deleteConversation(): void
    {
        if ($this->conversationToDelete) {
            $conv = Conversation::forUser(auth()->id())->find($this->conversationToDelete);
            if ($conv) {
                $wasActive = ($this->activeConversationId === $conv->id);
                $conv->delete();

                if ($wasActive) {
                    $this->newConversation();
                }
            }
        }

        $this->showDeleteModal = false;
        $this->conversationToDelete = null;
    }

    /**
     * Mengambil JSON Payload DeepSeek saat ini untuk keperluan debugging / inspeksi Sliding Window.
     */
    #[Computed]
    public function payloadJson(): string
    {
        $convId = $this->activeConversation?->id ?? 'dummy-new-session';
        $payload = DeepSeekService::buildDeepSeekPayload($convId);
        return json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}; ?>

<div class="h-full w-full flex overflow-hidden bg-[#f5f6f3] relative" x-data="{
    sidebarOpen: @entangle('sidebarOpen'),
    isMobile: window.innerWidth < 768,
    init() {
        window.addEventListener('resize', () => {
            this.isMobile = window.innerWidth < 768;
            if (this.isMobile) {
                this.sidebarOpen = false;
            }
        });
        if (this.isMobile) {
            this.sidebarOpen = false;
        }
        this.scrollToBottom();
    },
    scrollToBottom() {
        this.$nextTick(() => {
            const container = document.getElementById('chat-messages-container');
            if (container) {
                container.scrollTo({ top: container.scrollHeight, behavior: 'smooth' });
            }
        });
    }
}" @scroll-to-bottom.window="scrollToBottom()">

    {{-- MOBILE BACKDROP OVERLAY --}}
    <div
        x-show="sidebarOpen && isMobile"
        @click="sidebarOpen = false"
        x-transition:enter="transition-opacity ease-linear duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition-opacity ease-linear duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-[#17343d]/60 backdrop-blur-sm z-40 md:hidden"
    ></div>

    {{-- SIDEBAR: Riwayat Percakapan (Gemini / ChatGPT Style) --}}
    <aside
        class="h-full flex flex-col border-r border-[#dce1de]/80 bg-white transition-all duration-300 ease-in-out shrink-0 select-none z-50 md:static fixed inset-y-0 left-0"
        :class="sidebarOpen ? 'w-72 md:w-80 shadow-none md:shadow-none translate-x-0' : 'w-0 -translate-x-full md:translate-x-0 md:w-0 border-r-0 opacity-0 overflow-hidden'"
    >
        {{-- Top Bar: Back to Dashboard & New Chat --}}
        <div class="p-3.5 border-b border-[#dce1de]/70 bg-[#f5f6f3]/80 space-y-3 shrink-0">
            <div class="flex items-center justify-between gap-2">
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-2 text-[#344950] hover:text-[#315b50] font-bold text-xs group py-1.5 px-2.5 rounded-lg hover:bg-[#e6eae7]/60 transition-all"
                    title="Kembali ke Dashboard Utama"
                >
                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#e8efeb] text-[#315b50] group-hover:bg-[#244e49] group-hover:text-white transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    </span>
                    <span>Beranda ERP</span>
                </a>

                <button
                    @click="sidebarOpen = false"
                    type="button"
                    title="Tutup Sidebar"
                    class="p-1.5 rounded-lg text-[#71817f] hover:text-[#344950] hover:bg-[#e6eae7]/60 transition-colors"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/></svg>
                </button>
            </div>

            {{-- New Chat Button --}}
            <button
                wire:click="newConversation"
                @click="if(isMobile) sidebarOpen = false"
                type="button"
                class="btn-sound w-full flex items-center justify-center gap-2 py-2.5 px-3 rounded-lg bg-gradient-to-r from-[#244e49] to-[#244e49] hover:from-[#17343d] hover:to-[#17343d] text-white font-bold text-xs shadow-none  transition-all duration-150 active:scale-95"
            >
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Percakapan Baru</span>
            </button>
        </div>

        {{-- Sidebar Content: Grouped Conversation History --}}
        <div class="flex-1 overflow-y-auto p-3 space-y-5 scrollbar-thin scrollbar-thumb-[#dce1de]">
            @php $grouped = $this->groupedConversations; @endphp

            @if($grouped->isEmpty())
                <div class="py-12 px-4 text-center">
                    <div class="w-12 h-12 mx-auto mb-3 rounded-lg bg-[#f5f6f3] text-[#527c70] flex items-center justify-center shadow-none">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                    </div>
                    <p class="text-xs font-bold text-[#344950]">Belum ada riwayat</p>
                    <p class="text-[11px] text-[#71817f] mt-1">Mulai percakapan pertama Anda dengan AI Assistant.</p>
                </div>
            @else
                @foreach(['Hari Ini', 'Kemarin', '7 Hari Terakhir', '30 Hari Terakhir', 'Lebih Lama'] as $groupName)
                    @if(isset($grouped[$groupName]) && $grouped[$groupName]->isNotEmpty())
                        <div class="space-y-1.5">
                            <div class="px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider text-[#71817f]">
                                {{ $groupName }}
                            </div>

                            <div class="space-y-1">
                                @foreach($grouped[$groupName] as $conv)
                                    <div
                                        wire:key="conv-item-{{ $conv->id }}"
                                        class="group relative flex items-center justify-between rounded-lg px-2.5 py-2 text-xs font-medium transition-all duration-150 {{ $activeConversationId === $conv->id ? 'bg-[#244e49] text-white shadow-none  font-bold' : 'text-[#344950] hover:bg-[#eef2ee]' }}"
                                    >
                                        {{-- Inline Rename Form --}}
                                        @if($editingConversationId === $conv->id)
                                            <form wire:submit.prevent="saveRename" class="w-full flex items-center gap-1">
                                                <input
                                                    type="text"
                                                    wire:model="editingTitle"
                                                    wire:keydown.escape="cancelRename"
                                                    class="w-full text-xs px-2 py-1 rounded-lg border border-[#b7d4ce] bg-white text-[#344950] focus:outline-none focus:ring-1 focus:ring-[#527c70]"
                                                    autofocus
                                                />
                                                <button type="submit" class="p-1 rounded bg-[#527c70] text-white hover:bg-[#2f6b5c]" title="Simpan">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                                <button type="button" wire:click="cancelRename" class="p-1 rounded bg-[#dce1de] text-[#344950] hover:bg-[#a9beb5]" title="Batal">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </form>
                                        @else
                                            {{-- Button Switch Session --}}
                                            <button
                                                wire:click="selectConversation('{{ $conv->id }}')"
                                                @click="if(isMobile) sidebarOpen = false"
                                                type="button"
                                                class="flex-1 flex items-center gap-2.5 min-w-0 text-left pr-2 truncate"
                                            >
                                                <svg class="w-4 h-4 shrink-0 {{ $activeConversationId === $conv->id ? 'text-white' : 'text-[#71817f] group-hover:text-[#52646a]' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                                <span class="truncate">{{ $conv->title }}</span>
                                            </button>

                                            {{-- Action Menu: Rename & Delete --}}
                                            <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                                <button
                                                    wire:click.stop="startRename('{{ $conv->id }}')"
                                                    type="button"
                                                    title="Ganti Nama"
                                                    class="p-1 rounded-md {{ $activeConversationId === $conv->id ? 'hover:bg-[#17343d] text-[#e8efeb] hover:text-white' : 'hover:bg-[#e6eae7] text-[#71817f] hover:text-[#344950]' }}"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                </button>
                                                <button
                                                    wire:click.stop="confirmDelete('{{ $conv->id }}')"
                                                    type="button"
                                                    title="Hapus Percakapan"
                                                    class="p-1 rounded-md {{ $activeConversationId === $conv->id ? 'hover:bg-[#17343d] text-[#ead6d4] hover:text-[#f7eceb]' : 'hover:bg-[#f7eceb] text-[#71817f] hover:text-[#9c5252]' }}"
                                                >
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>

        {{-- Sidebar Footer: Memory & Token Status --}}
        <div class="p-3 border-t border-[#dce1de]/70 bg-[#f5f6f3]/50 text-[11px] text-[#64747a] shrink-0 space-y-2">

            {{-- Baris Pertama: Status + Toggle Token Stats (Admin) --}}
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-1.5 font-semibold">
                    <span class="w-2 h-2 rounded-full bg-[#527c70] animate-pulse"></span>
                    <span>Sliding Window Active</span>
                </div>
                <div class="flex items-center gap-2">
                    @if(auth()->user()->is_admin && $this->activeConversationId)
                        <button
                            wire:click="$toggle('showTokenStats')"
                            class="text-[#315b50] hover:text-[#315b50] font-bold hover:underline flex items-center gap-1"
                            title="Lihat Token Usage Sesi Ini"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            Token
                        </button>
                    @endif
                    <button
                        wire:click="$set('showPayloadModal', true)"
                        class="text-[#315b50] hover:text-[#17343d] font-bold hover:underline"
                    >
                        Payload
                    </button>
                </div>
            </div>

            {{-- Panel Token Stats (hanya admin, hanya saat sesi aktif & toggle on) --}}
            @if(auth()->user()->is_admin && $this->showTokenStats && $this->activeConversationId)
                @php $ts = $this->tokenStats; @endphp
                @if(!empty($ts))
                    <div class="rounded-lg bg-white border border-[#b7d4ce] p-2.5 space-y-2 shadow-none">
                        <p class="font-extrabold text-[10px] text-[#315b50] uppercase tracking-wider">📊 Token Usage — Sesi Ini</p>

                        {{-- Total --}}
                        <div class="grid grid-cols-2 gap-x-3 gap-y-1 text-[10px]">
                            <div class="flex justify-between">
                                <span class="text-[#64747a]">Total Token</span>
                                <span class="font-bold text-[#344950]">{{ number_format($ts['totalTokens']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#64747a]">Prompt</span>
                                <span class="font-semibold text-[#344950]">{{ number_format($ts['totalPrompt']) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#64747a]">Output</span>
                                <span class="font-semibold text-[#344950]">{{ number_format($ts['totalCompletion']) }}</span>
                            </div>
                        </div>

                        {{-- Cache Hit Bar --}}
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-[#64747a]">Cache Hit</span>
                                <span class="font-bold {{ $ts['hitRatio'] >= 60 ? 'text-[#2f6b5c]' : ($ts['hitRatio'] >= 30 ? 'text-[#5e4820]' : 'text-[#9c5252]') }}">
                                    {{ $ts['hitRatio'] }}%
                                    ({{ number_format($ts['cacheHit']) }} tk)
                                </span>
                            </div>
                            <div class="w-full bg-[#eef2ee] rounded-full h-1.5 overflow-hidden">
                                <div
                                    class="h-1.5 rounded-full {{ $ts['hitRatio'] >= 60 ? 'bg-[#527c70]' : ($ts['hitRatio'] >= 30 ? 'bg-[#ddcba7]' : 'bg-[#9c5252]') }}"
                                    style="width: {{ $ts['hitRatio'] }}%"
                                ></div>
                            </div>
                        </div>

                        {{-- Cost --}}
                        <div class="grid grid-cols-2 gap-x-3 text-[10px]">
                            <div class="flex justify-between">
                                <span class="text-[#64747a]">Biaya Est.</span>
                                <span class="font-bold text-[#344950]">\${{ number_format($ts['costUsd'], 5) }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-[#64747a]">Hemat</span>
                                <span class="font-bold text-[#2f6b5c]">+\${{ number_format($ts['savedUsd'], 5) }}</span>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-center text-[10px] text-[#71817f] italic py-1">Belum ada data token untuk sesi ini.</p>
                @endif
            @endif
        </div>
    </aside>

    {{-- MAIN CHAT AREA (Lebar Penuh) --}}
    <main class="flex-1 flex flex-col h-full bg-white relative min-w-0 overflow-hidden">

        {{-- Top Navbar Chat --}}
        <header class="h-14 sm:h-16 px-4 sm:px-6 border-b border-[#dce1de]/80 flex items-center justify-between gap-3 bg-white/95 backdrop-blur z-10 shrink-0">
            <div class="flex items-center gap-2.5 sm:gap-3 min-w-0">
                {{-- Open Sidebar Toggle --}}
                <button
                    @click="sidebarOpen = !sidebarOpen"
                    type="button"
                    class="p-2 -ml-2 rounded-lg text-[#64747a] hover:text-[#344950] hover:bg-[#eef2ee] transition-colors"
                    title="Toggle Riwayat Percakapan"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="w-8 h-8 sm:w-9 sm:h-9 rounded-lg bg-gradient-to-tr from-[#244e49] to-[#244e49] text-white flex items-center justify-center shrink-0 shadow-none  font-extrabold text-xs sm:text-sm">
                    AI
                </div>

                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="font-extrabold text-[#172c35] text-xs sm:text-sm md:text-base truncate max-w-[200px] sm:max-w-xs md:max-w-md">
                            {{ $this->activeConversation ? $this->activeConversation->title : 'Percakapan Baru' }}
                        </h2>
                        <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-[#f5f6f3] text-[#17343d] border border-[#dce1de]/60">
                            DeepSeek-V3
                        </span>
                    </div>
                    <p class="text-[10px] sm:text-[11px] text-[#71817f] truncate hidden xs:block">
                        Maks. {{ config('deepseek.max_context_messages', 10) }} pesan terakhir &bull; Context Caching Ready
                    </p>
                </div>
            </div>

            {{-- Action Tools: Dashboard Link, Minimize, JSON Inspector, Delete --}}
            <div class="flex items-center gap-1 sm:gap-2 shrink-0">
                {{-- Tombol Kembali ke Dashboard --}}
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="flex items-center gap-1.5 py-1.5 px-2.5 sm:px-3 rounded-lg bg-[#eef2ee] hover:bg-[#f5f6f3] text-[#344950] hover:text-[#315b50] text-xs font-bold transition-all border border-[#dce1de]/80"
                    title="Kembali ke Dashboard Utama"
                >
                    <svg class="w-4 h-4 text-[#315b50]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10.5L12 3l9 7.5M5.5 9v11h13V9M9 20v-6h6v6"/></svg>
                    <span class="hidden sm:inline">Dashboard</span>
                </a>

                {{-- Tombol Minimize Chat (Kembali ke Dashboard dengan Floating Widget Aktif) --}}
                <a
                    href="{{ route('dashboard') }}"
                    wire:navigate
                    class="p-2 rounded-lg text-[#71817f] hover:text-[#344950] hover:bg-[#eef2ee] transition-colors"
                    title="Minimize ke Floating Widget"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </a>

                {{-- Tombol Payload JSON --}}
                <button
                    wire:click="$set('showPayloadModal', true)"
                    type="button"
                    class="hidden lg:flex items-center gap-1.5 py-1.5 px-3 rounded-lg border border-[#dce1de] text-[#52646a] hover:text-[#315b50] hover:bg-[#f5f6f3]/50 text-xs font-semibold transition-all"
                    title="Lihat Payload Sliding Window"
                >
                    <svg class="w-4 h-4 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/></svg>
                    <span>Payload JSON</span>
                </button>

                @if($this->activeConversation)
                    <button
                        wire:click="confirmDelete('{{ $this->activeConversationId }}')"
                        type="button"
                        class="p-2 rounded-lg text-[#71817f] hover:text-[#9c5252] hover:bg-[#f7eceb] transition-colors"
                        title="Hapus Sesi Percakapan"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                @endif
            </div>
        </header>

        {{-- Message View Area --}}
        <div id="chat-messages-container" class="flex-1 overflow-y-auto px-4 sm:px-6 py-6 scroll-smooth">
            <div class="max-w-3xl lg:max-w-4xl mx-auto w-full space-y-6">
                @php $activeConv = $this->activeConversation; @endphp

                @if(! $activeConv || $activeConv->messages->isEmpty())
                    {{-- Empty State / Welcome Screen --}}
                    <div class="py-12 sm:py-16 text-center space-y-6">
                        <div class="w-16 h-16 sm:w-20 sm:h-20 mx-auto rounded-lg bg-gradient-to-tr from-[#244e49] via-[#244e49] to-[#315b50] text-white flex items-center justify-center shadow-none  animate-bounce">
                            <svg class="w-8 h-8 sm:w-10 sm:h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>

                        <div class="space-y-2">
                            <h3 class="text-xl sm:text-2xl font-black text-[#344950]">
                                Asisten Cerdas KULKAS
                            </h3>
                            <p class="text-xs sm:text-sm text-[#64747a] max-w-md mx-auto">
                                Tanyakan apa saja seputar inventaris gudang, analisis kas pembukuan, pengadaan stok supplier, atau rekomendasi SPK Restock.
                            </p>
                        </div>

                        {{-- Quick Prompts Suggestions --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-left pt-2 max-w-2xl mx-auto">
                            <button
                                wire:click="sendSuggestedPrompt('Bagaimana cara membuat seeder Laravel yang aman?')"
                                type="button"
                                class="btn-sound p-3.5 rounded-lg border border-[#dce1de]/90 bg-[#f5f6f3]/70 hover:bg-[#f5f6f3]/70 hover:border-[#b7d4ce] transition-all text-xs font-semibold text-[#344950] hover:text-[#17343d] group shadow-none flex items-start gap-2.5"
                            >
                                <span class="text-base leading-none">💡</span>
                                <div>
                                    <div class="font-bold text-[#172c35] group-hover:text-[#17343d]">Seeder Laravel Aman</div>
                                    <div class="text-[11px] text-[#64747a] font-normal">Contoh implementasi idempotent seeder</div>
                                </div>
                            </button>

                            <button
                                wire:click="sendSuggestedPrompt('Tolong analisis produk stok menipis dan berikan rekomendasi restock')"
                                type="button"
                                class="btn-sound p-3.5 rounded-lg border border-[#dce1de]/90 bg-[#f5f6f3]/70 hover:bg-[#f5f6f3]/70 hover:border-[#b7d4ce] transition-all text-xs font-semibold text-[#344950] hover:text-[#17343d] group shadow-none flex items-start gap-2.5"
                            >
                                <span class="text-base leading-none">📦</span>
                                <div>
                                    <div class="font-bold text-[#172c35] group-hover:text-[#17343d]">Analisis Stok Kritis</div>
                                    <div class="text-[11px] text-[#64747a] font-normal">Cek produk mendekati safety stock</div>
                                </div>
                            </button>

                            <button
                                wire:click="sendSuggestedPrompt('Jelaskan bagaimana perhitungan margin keuntungan dan omzet')"
                                type="button"
                                class="btn-sound p-3.5 rounded-lg border border-[#dce1de]/90 bg-[#f5f6f3]/70 hover:bg-[#f5f6f3]/70 hover:border-[#b7d4ce] transition-all text-xs font-semibold text-[#344950] hover:text-[#17343d] group shadow-none flex items-start gap-2.5"
                            >
                                <span class="text-base leading-none">📊</span>
                                <div>
                                    <div class="font-bold text-[#172c35] group-hover:text-[#17343d]">Margin & Arus Kas</div>
                                    <div class="text-[11px] text-[#64747a] font-normal">Formula laba kotor & ringkasan kas</div>
                                </div>
                            </button>

                            <button
                                wire:click="sendSuggestedPrompt('Bagaimana alur Purchase Request dan integrasi ke Supplier?')"
                                type="button"
                                class="btn-sound p-3.5 rounded-lg border border-[#dce1de]/90 bg-[#f5f6f3]/70 hover:bg-[#f5f6f3]/70 hover:border-[#b7d4ce] transition-all text-xs font-semibold text-[#344950] hover:text-[#17343d] group shadow-none flex items-start gap-2.5"
                            >
                                <span class="text-base leading-none">🧾</span>
                                <div>
                                    <div class="font-bold text-[#172c35] group-hover:text-[#17343d]">Alur Purchase Request</div>
                                    <div class="text-[11px] text-[#64747a] font-normal">Drafting, approval, dan penerimaan stok</div>
                                </div>
                            </button>
                        </div>
                    </div>
                @else
                    {{-- Message Timeline --}}
                    @foreach($this->messageGroups as $index => $group)
                        @if($group['type'] === 'process')
                            {{-- CARD PROSES BERPIKIR & KUERI DATABASE (BISA HIDE & UNHIDE) --}}
                            <div wire:key="process-group-{{ $index }}" x-data="{ open: false }" class="my-2 sm:pl-11">
                                <button
                                    @click="open = !open"
                                    type="button"
                                    class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-xs font-semibold bg-[#f5f6f3]/90 hover:bg-[#e8efeb] text-[#17343d] border border-[#dce1de]/80 transition-all shadow-xs group cursor-pointer"
                                    :title="open ? 'Sembunyikan langkah proses berpikir' : 'Buka detail proses berpikir AI'"
                                >
                                    <span class="flex h-2 w-2 relative">
                                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-[#527c70] opacity-75"></span>
                                        <span class="relative inline-flex rounded-full h-2 w-2 bg-[#244e49]"></span>
                                    </span>
                                    <span class="font-bold">Proses Berpikir & Akses Database</span>
                                    <span class="text-[10px] text-[#315b50] bg-white/90 px-1.5 py-0.5 rounded-full font-mono font-bold border border-[#dce1de]/60">{{ count($group['items']) }} aksi</span>
                                    <span class="text-[10px] text-[#315b50] font-bold ml-1 flex items-center gap-1">
                                        <span x-text="open ? 'Sembunyikan' : 'Lihat Proses'"></span>
                                        <svg :class="open ? 'rotate-180' : ''" class="w-3 h-3 text-[#315b50] transition-transform duration-200 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"/></svg>
                                    </span>
                                </button>

                                {{-- Detail Langkah Proses yang bisa di-hide & di-unhide --}}
                                <div x-show="open" x-transition.duration.200ms class="mt-2.5 space-y-2 pl-3 border-l-2 border-[#dce1de] ml-3">
                                    @foreach($group['items'] as $item)
                                        @if($item->role === 'assistant' && $item->tool_calls_json)
                                            @php
                                                $toolCalls = json_decode($item->tool_calls_json, true) ?: [];
                                            @endphp
                                            @foreach($toolCalls as $call)
                                                @php
                                                    $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                                                    $desc = $args['description'] ?? 'Membaca data inventaris & pembukuan ERP...';
                                                    $sql = $args['sql'] ?? null;
                                                @endphp
                                                <div class="inline-flex flex-col gap-1 text-[11px] text-[#17343d] bg-[#f5f6f3]/90 border border-[#dce1de]/80 rounded-lg px-3 py-2 shadow-xs max-w-xl">
                                                    <div class="flex items-center gap-2 font-semibold">
                                                        <span>🌐</span>
                                                        <span>{{ $desc }}</span>
                                                    </div>
                                                    @if($sql)
                                                        <div class="mt-1 font-mono text-[10px] text-[#344950] bg-white/90 p-2 rounded-lg border border-[#dce1de] overflow-x-auto whitespace-pre-wrap">
                                                            {{ $sql }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        @elseif($item->role === 'tool')
                                            <details class="text-[11px] text-[#52646a] bg-white hover:bg-[#f5f6f3] border border-[#dce1de] rounded-lg px-3.5 py-2 transition-all cursor-pointer max-w-xl group shadow-xs">
                                                <summary class="font-medium flex items-center gap-2 list-none select-none">
                                                    <span class="text-xs">📦</span>
                                                    <span class="font-bold text-[#344950]">Hasil Kueri Database ERP (Read-Only)</span>
                                                    <span class="text-[10px] text-[#71817f] group-open:rotate-90 transition-transform">▸</span>
                                                </summary>
                                                <pre class="mt-2 text-[10px] bg-[#17343d] text-[#527c70] p-2.5 rounded-lg overflow-x-auto whitespace-pre-wrap font-mono max-h-48 scrollbar-thin">{{ $item->content }}</pre>
                                            </details>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            {{-- Pesan Biasa (User / Final Assistant) --}}
                            @php $msg = $group['message']; @endphp
                            <div wire:key="msg-{{ $msg->id }}" class="flex {{ $msg->isUser() ? 'justify-end' : 'justify-start' }} gap-3 w-full">

                                @if(! $msg->isUser())
                                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#244e49] to-[#244e49] text-white flex items-center justify-center shrink-0 shadow-none font-extrabold text-xs mt-0.5">
                                        AI
                                    </div>
                                @endif

                                <div class="space-y-1 {{ $msg->isUser() ? 'max-w-[85%] sm:max-w-[75%]' : 'flex-1 max-w-[95%] sm:max-w-[88%]' }}">
                                    <div class="rounded-lg px-4 py-3 sm:px-5 sm:py-3.5 text-xs sm:text-sm leading-relaxed shadow-none {{ $msg->isUser() ? 'bg-gradient-to-r from-[#244e49] to-[#244e49] text-white rounded-tr-sm' : 'bg-[#eef2ee]/90 text-[#344950] border border-[#dce1de]/70 rounded-tl-sm' }}">
                                        @if($msg->isUser())
                                            <div class="text-white whitespace-pre-wrap font-sans text-xs sm:text-sm">
                                                {{ $msg->content }}
                                            </div>
                                        @else
                                            @if($streamingMessageId === $msg->id)
                                                {{-- Saat streaming aktif: gunakan marked.js + MutationObserver untuk live render chunk demi chunk --}}
                                                <div
                                                    x-data="{
                                                        rawText: '',
                                                        observer: null,
                                                        init() {
                                                            const target = this.$refs.rawStream;
                                                            const output = this.$refs.streamOutput;
                                                            if (target && output) {
                                                                const updateMarkdown = () => {
                                                                    const text = target.textContent || '';
                                                                    if (typeof marked !== 'undefined') {
                                                                        output.innerHTML = marked.parse(text);
                                                                    } else {
                                                                        output.textContent = text;
                                                                    }
                                                                };
                                                                this.observer = new MutationObserver(updateMarkdown);
                                                                this.observer.observe(target, { childList: true, characterData: true, subtree: true });
                                                                updateMarkdown();
                                                            }
                                                        },
                                                        destroy() {
                                                            if (this.observer) this.observer.disconnect();
                                                        }
                                                    }"
                                                    class="relative"
                                                >
                                                    {{-- Elemen tersembunyi penerima stream chunks dari Livewire wire:stream --}}
                                                    <span x-ref="rawStream" class="hidden" wire:stream="stream-msg-{{ $msg->id }}">{{ $msg->content }}</span>
                                                    {{-- Output render Markdown dinamis --}}
                                                    <div x-ref="streamOutput" class="markdown-content"></div>
                                                    <span class="inline-block w-1.5 h-3.5 bg-[#244e49] animate-pulse ml-0.5 align-middle rounded-xs"></span>
                                                </div>
                                            @else
                                                {{-- Pesan selesai / riwayat: render langsung dengan Str::markdown() Laravel (aman, super cepat, clean) --}}
                                                <div class="markdown-content">
                                                    {!! Str::markdown($msg->content) !!}
                                                </div>
                                            @endif
                                        @endif
                                    </div>
                                    <div class="text-[10px] text-[#71817f] px-1 {{ $msg->isUser() ? 'text-right' : 'text-left' }}">
                                        {{ $msg->created_at->format('H:i') }}
                                    </div>

                                    {{-- Token Badge: hanya tampil untuk pesan AI + user adalah admin --}}
                                    @if(!$msg->isUser() && $msg->role === 'assistant' && auth()->user()->is_admin && $msg->tokenUsage)
                                        @php $tu = $msg->tokenUsage; @endphp
                                        <div class="flex items-center gap-1.5 px-1 mt-0.5 flex-wrap">
                                            <span class="inline-flex items-center gap-1 rounded-full bg-[#eef2ee] border border-[#dce1de] px-1.5 py-0.5 text-[9px] font-semibold text-[#64747a]" title="Total token: prompt + output">
                                                <svg class="w-2.5 h-2.5 text-[#71817f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                                                {{ number_format($tu->total_tokens) }} tk
                                            </span>
                                            @if($tu->cache_hit_tokens > 0)
                                                @php $ratio = $tu->prompt_tokens > 0 ? round(($tu->cache_hit_tokens / $tu->prompt_tokens) * 100) : 0; @endphp
                                                <span class="inline-flex items-center gap-1 rounded-full bg-[#e8efeb] border border-[#b7d4ce] px-1.5 py-0.5 text-[9px] font-semibold text-[#2f6b5c]" title="Cache hit tokens: token prompt yang di-cache DeepSeek (lebih murah)">
                                                    ⚡ {{ $ratio }}% cache
                                                </span>
                                            @elseif($tu->cache_miss_tokens > 0)
                                                <span class="inline-flex items-center gap-1 rounded-full bg-[#f7f1e4] border border-[#ddcba7] px-1.5 py-0.5 text-[9px] font-semibold text-[#5e4820]" title="Cache miss: prefix belum di-cache">
                                                    ⚠ no cache
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                @if($msg->isUser())
                                    <div class="w-8 h-8 rounded-lg bg-[#1d3d45] text-white flex items-center justify-center shrink-0 shadow-none font-bold text-xs uppercase mt-0.5">
                                        {{ substr(auth()->user()->name ?? 'U', 0, 1) }}
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endif

                {{-- Indikator AI Sedang Menyiapkan Respons --}}
                <div wire:loading wire:target="sendMessage, sendSuggestedPrompt" class="flex justify-start gap-3 w-full" x-show="!$wire.streamingMessageId">
                    <div class="w-8 h-8 rounded-lg bg-gradient-to-tr from-[#244e49] to-[#244e49] text-white flex items-center justify-center shrink-0 shadow-none font-extrabold text-xs animate-pulse">
                        AI
                    </div>
                    <div class="rounded-lg rounded-tl-sm px-5 py-3.5 bg-[#eef2ee] border border-[#dce1de]/70 flex items-center gap-2 shadow-none">
                        <span class="w-2 h-2 rounded-full bg-[#244e49] animate-bounce"></span>
                        <span class="w-2 h-2 rounded-full bg-[#244e49] animate-bounce [animation-delay:0.2s]"></span>
                        <span class="w-2 h-2 rounded-full bg-[#244e49] animate-bounce [animation-delay:0.4s]"></span>
                        <span class="text-xs font-semibold text-[#64747a] ml-1">AI sedang menganalisis context...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Chat Input Bar (Sticky at Bottom) --}}
        <div class="p-3 sm:p-4 border-t border-[#dce1de]/80 bg-white/95 backdrop-blur shrink-0">
            <div class="max-w-3xl lg:max-w-4xl mx-auto w-full">
                <form
                    wire:submit.prevent="sendMessage"
                    class="relative flex items-end gap-2 bg-[#f5f6f3] border border-[#cbd3d0]/80 rounded-lg p-1.5 focus-within:border-[#527c70] focus-within:ring-2 focus-within:ring-[#527c70]/20 transition-all shadow-none"
                    x-data="{
                        init() {
                            this.$nextTick(() => this.$refs.input?.focus());
                        },
                        handleKeydown(e) {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                $wire.sendMessage();
                            }
                        }
                    }"
                    @focus-input.window="$refs.input?.focus()"
                >
                    <textarea
                        x-ref="input"
                        wire:model="messageInput"
                        @keydown="handleKeydown($event)"
                        :disabled="$wire.streamingMessageId !== null"
                        rows="1"
                        placeholder="Tulis pesan atau pertanyaan Anda di sini... (Enter untuk kirim, Shift+Enter untuk baris baru)"
                        class="w-full resize-none border-0 bg-transparent px-3 py-2 text-xs sm:text-sm text-[#344950] placeholder-[#9aa6a3] focus:outline-none focus:ring-0 max-h-32 min-h-[40px] disabled:opacity-60"
                    ></textarea>

                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        :disabled="$wire.streamingMessageId !== null"
                        class="btn-sound h-10 w-10 sm:h-11 sm:w-11 rounded-lg bg-gradient-to-r from-[#244e49] to-[#244e49] hover:from-[#17343d] hover:to-[#17343d] text-white flex items-center justify-center shrink-0 shadow-none  transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                        title="Kirim Pesan"
                    >
                        <svg class="w-4 h-4 sm:w-5 sm:h-5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>

                <div class="flex items-center justify-between text-[10px] text-[#71817f] px-2 mt-1.5">
                    <span>DeepSeek Context Memory &bull; Sliding Window Active</span>
                    <span class="hidden sm:inline">Tekan Enter ↵ untuk kirim</span>
                </div>
            </div>
        </div>
    </main>

    {{-- MODAL: Konfirmasi Hapus Percakapan --}}
    @if($showDeleteModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#17343d]/50 backdrop-blur-sm animate-fadeIn">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-none border border-[#e6eae7] space-y-4">
                <div class="flex items-center gap-3 text-[#9c5252]">
                    <div class="p-3 rounded-lg bg-[#f7eceb]">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </div>
                    <div>
                        <h4 class="text-base font-extrabold text-[#172c35]">Hapus Percakapan?</h4>
                        <p class="text-xs text-[#64747a]">Tindakan ini akan menghapus seluruh riwayat pesan secara permanen.</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        wire:click="$set('showDeleteModal', false)"
                        type="button"
                        class="px-4 py-2 rounded-lg text-xs font-bold text-[#52646a] hover:bg-[#eef2ee] transition-colors"
                    >
                        Batal
                    </button>
                    <button
                        wire:click="deleteConversation"
                        type="button"
                        class="px-4 py-2 rounded-lg text-xs font-bold bg-[#7a3d3d] hover:bg-[#7a3d3d] text-white shadow-none  transition-all"
                    >
                        Ya, Hapus
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- MODAL: DeepSeek Sliding Window & Payload JSON Inspector --}}
    @if($showPayloadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#17343d]/60 backdrop-blur-sm animate-fadeIn">
            <div class="w-full max-w-3xl max-h-[85vh] rounded-lg bg-[#17343d] text-white p-6 shadow-none border border-[#a9beb5] flex flex-col space-y-4">
                <div class="flex items-center justify-between border-b border-[#a9beb5] pb-3">
                    <div class="flex items-center gap-2.5">
                        <span class="p-2 rounded-lg bg-[#315b50]/20 text-[#527c70] font-mono text-xs font-bold">{ }</span>
                        <div>
                            <h4 class="text-sm font-extrabold text-white">DeepSeek API Payload Builder</h4>
                            <p class="text-[11px] text-[#71817f]">Sliding Window Memory & Context Caching Structure (>1024 Token System Prompt)</p>
                        </div>
                    </div>
                    <button
                        wire:click="$set('showPayloadModal', false)"
                        type="button"
                        class="p-1.5 rounded-lg text-[#71817f] hover:text-white hover:bg-[#1d3d45]"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="flex-1 overflow-y-auto bg-[#17343d] p-4 rounded-lg border border-[#a9beb5]/80 font-mono text-xs text-[#527c70] select-all leading-relaxed">
                    <pre>{{ $this->payloadJson }}</pre>
                </div>

                <div class="flex items-center justify-between pt-1 text-[11px] text-[#71817f]">
                    <div>
                        Model: <span class="text-[#527c70] font-bold font-mono">{{ config('deepseek.model', 'deepseek-chat') }}</span> | Max Context: <span class="text-[#527c70] font-bold font-mono">{{ config('deepseek.max_context_messages', 10) }}</span> Pesan
                    </div>
                    <button
                        wire:click="$set('showPayloadModal', false)"
                        class="px-4 py-2 rounded-lg bg-[#244e49] hover:bg-[#17343d] text-white font-bold text-xs"
                    >
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
