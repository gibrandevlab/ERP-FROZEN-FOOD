<?php

use App\Models\ChatMessage;
use App\Models\Conversation;
use App\Services\DeepSeekService;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component {
    public bool $isOpen = false;
    public ?string $activeConversationId = null;
    public string $messageInput = '';
    public ?string $streamingMessageId = null;

    public function mount(): void
    {
        $lastConv = Conversation::forUser(auth()->id())->latest('updated_at')->first();
        if ($lastConv) {
            $this->activeConversationId = $lastConv->id;
        }
    }

    public function toggleWidget(): void
    {
        $this->isOpen = ! $this->isOpen;
        if ($this->isOpen) {
            $this->dispatch('floating-scroll-bottom');
            $this->dispatch('floating-focus-input');
        }
    }

    public function newConversation(): void
    {
        $this->activeConversationId = null;
        $this->messageInput = '';
        $this->streamingMessageId = null;
        $this->dispatch('floating-focus-input');
    }

    #[Computed]
    public function activeConversation()
    {
        if (! $this->activeConversationId) {
            return null;
        }

        return Conversation::with(['messages' => function ($q) {
            $q->with('tokenUsage')->orderBy('created_at', 'asc');
        }])->forUser(auth()->id())->find($this->activeConversationId);
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

        ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'user',
            'content'         => $userText,
        ]);

        $this->messageInput = '';
        $this->dispatch('floating-scroll-bottom');

        $assistantMsg = ChatMessage::create([
            'conversation_id' => $conversation->id,
            'role'            => 'assistant',
            'content'         => '',
        ]);

        $this->streamingMessageId = $assistantMsg->id;
        $streamTarget = 'stream-float-msg-' . $assistantMsg->id;

        $assistantReply = DeepSeekService::chatStream($conversation->id, function (string $chunk) use ($streamTarget) {
            $this->stream(to: $streamTarget, content: $chunk, replace: false);
            $this->dispatch('floating-scroll-bottom');
        }, $assistantMsg->id);

        $assistantMsg->update([
            'content' => $assistantReply,
        ]);

        $this->streamingMessageId = null;
        $conversation->touch();
        $this->dispatch('floating-scroll-bottom');
    }

    public function sendSuggestedPrompt(string $prompt): void
    {
        $this->messageInput = $prompt;
        $this->sendMessage();
    }
}; ?>

<div
    class="select-none"
    x-data="floatingChatEngine()"
    x-init="initWidget()"
    @floating-scroll-bottom.window="scrollBottom()"
>

    {{-- ========================================================================= --}}
    {{-- FLOATING TRIGGER BUBBLE (DRAGGABLE KE MANA SAJA)                          --}}
    {{-- ========================================================================= --}}
    <div
        x-show="!$wire.isOpen"
        x-ref="triggerBtn"
        @mousedown="startBtnDrag($event)"
        @touchstart.passive="startBtnTouchDrag($event)"
        class="fixed z-40 touch-none"
        :style="`left: ${btnX}px; top: ${btnY}px; transition: ${isDraggingBtn ? 'none' : 'box-shadow 0.2s, transform 0.15s'};`"
    >
        <button
            @click="handleBtnClick($event)"
            type="button"
            class="group relative flex items-center gap-2.5 py-3 px-4 rounded-full bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 hover:from-blue-700 hover:to-indigo-800 text-white shadow-2xl shadow-blue-600/40 hover:shadow-blue-600/60 active:scale-95 border-2 border-white cursor-grab active:cursor-grabbing transition-all"
            title="Tahan & geser untuk memindahkan, klik untuk buka chat"
        >
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white/20 text-white font-extrabold text-xs">
                ✨
            </span>
            <span class="text-xs font-extrabold tracking-wide hidden sm:inline">
                Tanya AI
            </span>
            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3.5 w-3.5 bg-emerald-500 border-2 border-white"></span>
            </span>
        </button>
    </div>

    {{-- ========================================================================= --}}
    {{-- DRAGGABLE & RESIZABLE FLOATING CHAT CARD                                  --}}
    {{-- ========================================================================= --}}
    @if($isOpen)
        <div
            x-ref="chatCard"
            class="fixed z-50 flex flex-col rounded-3xl border border-slate-200/90 bg-white/95 shadow-2xl shadow-blue-950/25 backdrop-blur-2xl overflow-hidden animate-fadeIn"
            :style="`left: ${cardX}px; top: ${cardY}px; width: ${cardW}px; height: ${cardH}px; min-width: 300px; min-height: 380px; max-width: calc(100vw - 20px); max-height: calc(100vh - 20px);`"
        >
            {{-- Header Card (Drag Handle) --}}
            <div
                @mousedown="startCardDrag($event)"
                @touchstart.passive="startCardTouchDrag($event)"
                class="px-4 py-3 bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white flex items-center justify-between gap-2 shrink-0 shadow-md cursor-move select-none"
                title="Tahan & geser header ini untuk memindahkan kartu"
            >
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="w-7 h-7 rounded-xl bg-white/20 flex items-center justify-center font-bold text-xs shrink-0">AI</span>
                    <div class="min-w-0">
                        <h4 class="font-extrabold text-xs sm:text-sm truncate">
                            {{ $this->activeConversation ? $this->activeConversation->title : 'Asisten AI Riza Frozen' }}
                        </h4>
                        <div class="flex items-center gap-1.5 text-[10px] text-blue-100">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                            <span>DeepSeek Sliding Window</span>
                        </div>
                    </div>
                </div>

                {{-- Action Tools: New, Size Toggle, Maximize, Close --}}
                <div class="flex items-center gap-1 shrink-0" @mousedown.stop @touchstart.stop>
                    {{-- Percakapan Baru --}}
                    <button
                        wire:click="newConversation"
                        type="button"
                        class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/15 transition-colors"
                        title="Percakapan Baru (+)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                    </button>

                    {{-- Quick Size Preset (Compact / Medium / Large) --}}
                    <button
                        @click="cycleSizePreset()"
                        type="button"
                        class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/15 transition-colors"
                        title="Ganti Ukuran Card (Compact / Normal / Lebar)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/></svg>
                    </button>

                    {{-- Maximize ke Fullscreen Route --}}
                    <a
                        href="{{ $activeConversationId ? route('chat.index', $activeConversationId) : route('chat.index') }}"
                        wire:navigate
                        class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/15 transition-colors"
                        title="Buka Layar Penuh (Fullscreen Mode)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>

                    {{-- Minimize / Tutup --}}
                    <button
                        wire:click="toggleWidget"
                        type="button"
                        class="p-1.5 rounded-lg text-blue-100 hover:text-white hover:bg-white/15 transition-colors"
                        title="Minimize ke Tombol Floating"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Message Body --}}
            <div id="floating-chat-scroll" class="flex-1 overflow-y-auto p-3.5 space-y-3.5 bg-slate-50/50 scrollbar-thin scrollbar-thumb-slate-200">
                @php $activeConv = $this->activeConversation; @endphp

                @if(! $activeConv || $activeConv->messages->isEmpty())
                    <div class="py-8 px-2 text-center space-y-4">
                        <div class="w-12 h-12 mx-auto rounded-2xl bg-blue-100/70 text-blue-600 flex items-center justify-center shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </div>
                        <div>
                            <p class="text-xs font-extrabold text-slate-800">Ada yang bisa dibantu?</p>
                            <p class="text-[11px] text-slate-500 mt-0.5">Tanya seputar stok gudang, penjualan, atau supplier.</p>
                        </div>

                        <div class="space-y-1.5 text-left">
                            <button
                                wire:click="sendSuggestedPrompt('Tolong cek stok produk yang mau habis')"
                                type="button"
                                class="w-full p-2 rounded-xl border border-slate-200 bg-white hover:bg-blue-50 text-[11px] font-semibold text-slate-700 hover:text-blue-700 transition-all text-left truncate flex items-center gap-2"
                            >
                                <span>📦</span>
                                <span class="truncate">Cek stok produk menipis</span>
                            </button>
                            <button
                                wire:click="sendSuggestedPrompt('Bagaimana ringkasan keuangan dan laba?')"
                                type="button"
                                class="w-full p-2 rounded-xl border border-slate-200 bg-white hover:bg-blue-50 text-[11px] font-semibold text-slate-700 hover:text-blue-700 transition-all text-left truncate flex items-center gap-2"
                            >
                                <span>📊</span>
                                <span class="truncate">Ringkasan kas dan laba</span>
                            </button>
                        </div>
                    </div>
                @else
                    @foreach($this->messageGroups as $index => $group)
                        @if($group['type'] === 'process')
                            {{-- CARD PROSES BERPIKIR & KUERI DATABASE FLOATING (BISA HIDE & UNHIDE) --}}
                            <div wire:key="float-proc-{{ $index }}" x-data="{ open: false }" class="my-1.5 pl-8">
                                <button
                                    @click="open = !open"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-blue-50/90 hover:bg-blue-100 text-blue-700 border border-blue-200/80 transition-all shadow-xs"
                                    :title="open ? 'Sembunyikan proses' : 'Lihat proses AI'"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                                    <span class="font-bold">Proses ERP ({{ count($group['items']) }} aksi)</span>
                                    <span class="text-[9px] font-bold" x-text="open ? 'Sembunyikan ▴' : 'Lihat ▸'"></span>
                                </button>
                                <div x-show="open" x-transition.duration.200ms class="mt-1.5 space-y-1.5 pl-2 border-l border-blue-200 ml-2">
                                    @foreach($group['items'] as $item)
                                        @if($item->role === 'assistant' && $item->tool_calls_json)
                                            @php
                                                $toolCalls = json_decode($item->tool_calls_json, true) ?: [];
                                            @endphp
                                            @foreach($toolCalls as $call)
                                                @php
                                                    $args = json_decode($call['function']['arguments'] ?? '{}', true) ?: [];
                                                    $desc = $args['description'] ?? 'Membaca data ERP...';
                                                @endphp
                                                <div class="text-[10px] text-blue-700 bg-blue-50/90 border border-blue-200/80 rounded-lg px-2 py-1">
                                                    🌐 {{ $desc }}
                                                </div>
                                            @endforeach
                                        @elseif($item->role === 'tool')
                                            <details class="text-[10px] text-slate-600 bg-white border border-slate-200 rounded-lg px-2 py-1">
                                                <summary class="font-medium cursor-pointer select-none">
                                                    📦 Hasil Kueri
                                                </summary>
                                                <pre class="mt-1 text-[9px] bg-slate-900 text-emerald-400 p-1.5 rounded font-mono max-h-32 overflow-x-auto whitespace-pre-wrap">{{ $item->content }}</pre>
                                            </details>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @else
                            @php $msg = $group['message']; @endphp
                            <div wire:key="float-msg-{{ $msg->id }}" class="flex {{ $msg->isUser() ? 'justify-end' : 'justify-start' }} gap-2">
                                @if(! $msg->isUser())
                                    <div class="w-6 h-6 rounded-lg bg-blue-600 text-white flex items-center justify-center shrink-0 font-bold text-[10px]">
                                        AI
                                    </div>
                                @endif

                                <div class="max-w-[88%] rounded-2xl px-3 py-2 text-xs {{ $msg->isUser() ? 'bg-blue-600 text-white rounded-tr-none' : 'bg-white text-slate-800 border border-slate-200/80 rounded-tl-none shadow-sm' }} leading-relaxed">
                                    @if($msg->isUser())
                                        <div class="whitespace-pre-wrap font-sans">
                                            {{ $msg->content }}
                                        </div>
                                    @else
                                        @if($streamingMessageId === $msg->id)
                                            <div
                                                x-data="{
                                                    rawText: '',
                                                    observer: null,
                                                    init() {
                                                        const target = this.$refs.rawStreamFloat;
                                                        const output = this.$refs.streamOutputFloat;
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
                                                <span x-ref="rawStreamFloat" class="hidden" wire:stream="stream-float-msg-{{ $msg->id }}">{{ $msg->content }}</span>
                                                <div x-ref="streamOutputFloat" class="markdown-content text-xs"></div>
                                                <span class="inline-block w-1.5 h-3 bg-blue-600 animate-pulse ml-0.5 align-middle rounded-xs"></span>
                                            </div>
                                        @else
                                            <div class="markdown-content text-xs">
                                                {!! Str::markdown($msg->content) !!}
                                            </div>
                                        @endif
                                    @endif
                                </div>

                                {{-- Token Badge mini (hanya admin, hanya pesan AI) --}}
                                @if(!$msg->isUser() && $msg->role === 'assistant' && auth()->user()->is_admin && $msg->tokenUsage)
                                    @php $tu = $msg->tokenUsage; $ratio = $tu->prompt_tokens > 0 ? round(($tu->cache_hit_tokens / $tu->prompt_tokens) * 100) : 0; @endphp
                                    <div class="flex items-center gap-1 mt-0.5 ml-0.5">
                                        <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-1.5 py-0.5 text-[8px] font-semibold text-slate-500">
                                            {{ number_format($tu->total_tokens) }} tk
                                        </span>
                                        @if($tu->cache_hit_tokens > 0)
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 border border-emerald-200 px-1.5 py-0.5 text-[8px] font-semibold text-emerald-600">⚡{{ $ratio }}%</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endif
                    @endforeach
                @endif

                {{-- Loader Typing --}}
                <div wire:loading wire:target="sendMessage, sendSuggestedPrompt" class="flex items-center gap-2 text-[11px] text-slate-500" x-show="!$wire.streamingMessageId">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-bounce"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-bounce [animation-delay:0.4s]"></span>
                    <span>AI sedang berpikir...</span>
                </div>
            </div>

            {{-- Input Mini Form & Bottom Resize Handle --}}
            <div class="p-2.5 bg-white border-t border-slate-200 shrink-0 relative">
                <form
                    wire:submit.prevent="sendMessage"
                    class="flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-xl p-1 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500"
                    x-data="{
                        init() {
                            this.$nextTick(() => this.$refs.floatInput?.focus());
                        }
                    }"
                    @floating-focus-input.window="$refs.floatInput?.focus()"
                >
                    <input
                        x-ref="floatInput"
                        type="text"
                        wire:model="messageInput"
                        :disabled="$wire.streamingMessageId !== null"
                        placeholder="Ketik pesan..."
                        class="w-full text-xs px-2 py-1.5 bg-transparent border-0 focus:outline-none focus:ring-0 text-slate-800 placeholder-slate-400 disabled:opacity-60"
                    />
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        :disabled="$wire.streamingMessageId !== null"
                        class="h-8 w-8 rounded-lg bg-blue-600 hover:bg-blue-700 text-white flex items-center justify-center shrink-0 shadow-sm disabled:opacity-50"
                    >
                        <svg class="w-3.5 h-3.5 transform rotate-90" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    </button>
                </form>

                {{-- Resize Handle Gripper (Pojok Kanan Bawah untuk Mengatur Ukuran) --}}
                <div
                    @mousedown.stop="startResize($event)"
                    @touchstart.stop.passive="startTouchResize($event)"
                    class="absolute bottom-1 right-1 w-4 h-4 cursor-se-resize flex items-end justify-end p-0.5 text-slate-400 hover:text-blue-600 touch-none"
                    title="Tarik untuk memperbesar / memperkecil ukuran card"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 19H13m6-6H7"/></svg>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    function floatingChatEngine() {
        return {
            // Posisi tombol trigger
            btnX: window.innerWidth - 135,
            btnY: window.innerHeight - 130,
            isDraggingBtn: false,
            btnStartX: 0,
            btnStartY: 0,
            btnInitialX: 0,
            btnInitialY: 0,
            btnMoved: false,

            // Posisi dan ukuran kartu chat
            cardX: Math.max(15, window.innerWidth - 425),
            cardY: Math.max(15, window.innerHeight - 565),
            cardW: 400,
            cardH: 520,
            isDraggingCard: false,
            cardStartX: 0,
            cardStartY: 0,
            cardInitialX: 0,
            cardInitialY: 0,

            // Resize state
            isResizing: false,
            resizeStartX: 0,
            resizeStartY: 0,
            resizeInitialW: 0,
            resizeInitialH: 0,

            initWidget() {
                // Load posisi tersimpan jika ada
                const savedPos = localStorage.getItem('riza_floating_chat_pos');
                if (savedPos) {
                    try {
                        const p = JSON.parse(savedPos);
                        if (p.btnX && p.btnX < window.innerWidth) this.btnX = p.btnX;
                        if (p.btnY && p.btnY < window.innerHeight) this.btnY = p.btnY;
                        if (p.cardX && p.cardX < window.innerWidth) this.cardX = p.cardX;
                        if (p.cardY && p.cardY < window.innerHeight) this.cardY = p.cardY;
                        if (p.cardW) this.cardW = Math.min(window.innerWidth - 20, Math.max(300, p.cardW));
                        if (p.cardH) this.cardH = Math.min(window.innerHeight - 20, Math.max(380, p.cardH));
                    } catch(e) {}
                }

                // Global pointer listener untuk drag & resize yang responsif
                window.addEventListener('mousemove', (e) => this.onMouseMove(e));
                window.addEventListener('mouseup', () => this.onMouseUp());
                window.addEventListener('touchmove', (e) => this.onTouchMove(e), { passive: false });
                window.addEventListener('touchend', () => this.onTouchEnd());

                window.addEventListener('resize', () => {
                    this.clampPositions();
                });
            },

            clampPositions() {
                this.btnX = Math.max(10, Math.min(window.innerWidth - 130, this.btnX));
                this.btnY = Math.max(10, Math.min(window.innerHeight - 70, this.btnY));
                this.cardX = Math.max(10, Math.min(window.innerWidth - this.cardW - 10, this.cardX));
                this.cardY = Math.max(10, Math.min(window.innerHeight - this.cardH - 10, this.cardY));
                this.saveState();
            },

            saveState() {
                localStorage.setItem('riza_floating_chat_pos', JSON.stringify({
                    btnX: this.btnX,
                    btnY: this.btnY,
                    cardX: this.cardX,
                    cardY: this.cardY,
                    cardW: this.cardW,
                    cardH: this.cardH
                }));
            },

            scrollBottom() {
                this.$nextTick(() => {
                    const el = document.getElementById('floating-chat-scroll');
                    if (el) el.scrollTop = el.scrollHeight;
                });
            },

            // --- DRAG TRIGGER BUTTON ---
            startBtnDrag(e) {
                if (e.button !== 0) return;
                this.isDraggingBtn = true;
                this.btnMoved = false;
                this.btnStartX = e.clientX;
                this.btnStartY = e.clientY;
                this.btnInitialX = this.btnX;
                this.btnInitialY = this.btnY;
            },
            startBtnTouchDrag(e) {
                const touch = e.touches[0];
                this.isDraggingBtn = true;
                this.btnMoved = false;
                this.btnStartX = touch.clientX;
                this.btnStartY = touch.clientY;
                this.btnInitialX = this.btnX;
                this.btnInitialY = this.btnY;
            },
            handleBtnClick(e) {
                if (!this.btnMoved) {
                    this.$wire.toggleWidget();
                }
            },

            // --- DRAG CHAT CARD ---
            startCardDrag(e) {
                if (e.button !== 0) return;
                this.isDraggingCard = true;
                this.cardStartX = e.clientX;
                this.cardStartY = e.clientY;
                this.cardInitialX = this.cardX;
                this.cardInitialY = this.cardY;
            },
            startCardTouchDrag(e) {
                const touch = e.touches[0];
                this.isDraggingCard = true;
                this.cardStartX = touch.clientX;
                this.cardStartY = touch.clientY;
                this.cardInitialX = this.cardX;
                this.cardInitialY = this.cardY;
            },

            // --- RESIZE CHAT CARD ---
            startResize(e) {
                if (e.button !== 0) return;
                this.isResizing = true;
                this.resizeStartX = e.clientX;
                this.resizeStartY = e.clientY;
                this.resizeInitialW = this.cardW;
                this.resizeInitialH = this.cardH;
            },
            startTouchResize(e) {
                const touch = e.touches[0];
                this.isResizing = true;
                this.resizeStartX = touch.clientX;
                this.resizeStartY = touch.clientY;
                this.resizeInitialW = this.cardW;
                this.resizeInitialH = this.cardH;
            },

            cycleSizePreset() {
                if (this.cardW <= 350) {
                    this.cardW = 420;
                    this.cardH = 540;
                } else if (this.cardW <= 450) {
                    this.cardW = Math.min(window.innerWidth - 40, 620);
                    this.cardH = Math.min(window.innerHeight - 40, 680);
                } else {
                    this.cardW = 340;
                    this.cardH = 460;
                }
                this.clampPositions();
            },

            onMouseMove(e) {
                if (this.isDraggingBtn) {
                    const dx = e.clientX - this.btnStartX;
                    const dy = e.clientY - this.btnStartY;
                    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) this.btnMoved = true;
                    this.btnX = Math.max(10, Math.min(window.innerWidth - 130, this.btnInitialX + dx));
                    this.btnY = Math.max(10, Math.min(window.innerHeight - 70, this.btnInitialY + dy));
                }
                if (this.isDraggingCard) {
                    const dx = e.clientX - this.cardStartX;
                    const dy = e.clientY - this.cardStartY;
                    this.cardX = Math.max(10, Math.min(window.innerWidth - this.cardW - 10, this.cardInitialX + dx));
                    this.cardY = Math.max(10, Math.min(window.innerHeight - this.cardH - 10, this.cardInitialY + dy));
                }
                if (this.isResizing) {
                    const dx = e.clientX - this.resizeStartX;
                    const dy = e.clientY - this.resizeStartY;
                    this.cardW = Math.min(window.innerWidth - this.cardX - 10, Math.max(300, this.resizeInitialW + dx));
                    this.cardH = Math.min(window.innerHeight - this.cardY - 10, Math.max(380, this.resizeInitialH + dy));
                }
            },

            onMouseUp() {
                if (this.isDraggingBtn || this.isDraggingCard || this.isResizing) {
                    this.isDraggingBtn = false;
                    this.isDraggingCard = false;
                    this.isResizing = false;
                    this.saveState();
                }
            },

            onTouchMove(e) {
                if (this.isDraggingBtn || this.isDraggingCard || this.isResizing) {
                    e.preventDefault();
                    const touch = e.touches[0];
                    if (this.isDraggingBtn) {
                        const dx = touch.clientX - this.btnStartX;
                        const dy = touch.clientY - this.btnStartY;
                        if (Math.abs(dx) > 4 || Math.abs(dy) > 4) this.btnMoved = true;
                        this.btnX = Math.max(10, Math.min(window.innerWidth - 130, this.btnInitialX + dx));
                        this.btnY = Math.max(10, Math.min(window.innerHeight - 70, this.btnInitialY + dy));
                    }
                    if (this.isDraggingCard) {
                        const dx = touch.clientX - this.cardStartX;
                        const dy = touch.clientY - this.cardStartY;
                        this.cardX = Math.max(10, Math.min(window.innerWidth - this.cardW - 10, this.cardInitialX + dx));
                        this.cardY = Math.max(10, Math.min(window.innerHeight - this.cardH - 10, this.cardInitialY + dy));
                    }
                    if (this.isResizing) {
                        const dx = touch.clientX - this.resizeStartX;
                        const dy = touch.clientY - this.resizeStartY;
                        this.cardW = Math.min(window.innerWidth - this.cardX - 10, Math.max(300, this.resizeInitialW + dx));
                        this.cardH = Math.min(window.innerHeight - this.cardY - 10, Math.max(380, this.resizeInitialH + dy));
                    }
                }
            },

            onTouchEnd() {
                this.onMouseUp();
            }
        };
    }
</script>
