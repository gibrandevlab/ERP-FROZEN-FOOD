<?php

use App\Models\{User, TokenLimit, TokenUsage, Conversation};
use Livewire\Attributes\{Computed, Layout};
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    // ── Akses Guard ───────────────────────────────────────────
    public function mount(): void
    {
        abort_unless(
            auth()->user()->is_admin || auth()->user()->canDo('token_usage', 'view'),
            403,
            'Anda tidak memiliki akses ke halaman ini.'
        );
    }

    // ── State ─────────────────────────────────────────────────
    public string $search           = '';
    public string $sortBy           = 'name';
    public string $sortDir          = 'asc';
    public string $filterStatus     = 'all'; // all | enabled | disabled | over_daily | over_monthly

    // Modal Edit Limit
    public bool $showLimitModal     = false;
    public ?int $editingUserId      = null;
    public string $editingUserName  = '';
    public bool $limitEnabled       = true;
    public string $dailyLimit       = '0';
    public string $monthlyLimit     = '0';
    public string $limitNotes       = '';

    // ── Computed: ringkasan global ────────────────────────────
    #[Computed]
    public function globalStats(): array
    {
        $today = now()->toDateString();
        $month = now()->format('Y-m');

        $totalTokensToday    = TokenUsage::whereDate('created_at', $today)->sum('total_tokens');
        $totalTokensMonth    = TokenUsage::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('total_tokens');
        $totalCacheHit       = TokenUsage::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('cache_hit_tokens');
        $totalPrompt         = TokenUsage::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('prompt_tokens');
        $totalCompletion     = TokenUsage::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->sum('completion_tokens');
        $hitRatio            = $totalPrompt > 0 ? round($totalCacheHit / $totalPrompt * 100, 1) : 0;
        $costMonth           = (($totalPrompt - $totalCacheHit) * 0.14 + $totalCacheHit * 0.014 + $totalCompletion * 0.28) / 1_000_000;
        $savedMonth          = ($totalCacheHit * 0.126) / 1_000_000;
        $activeUsers         = Conversation::distinct('user_id')->whereDate('updated_at', $today)->count('user_id');
        $disabledCount       = TokenLimit::where('is_enabled', false)->count();

        return compact(
            'totalTokensToday', 'totalTokensMonth', 'totalCacheHit',
            'totalPrompt', 'totalCompletion', 'hitRatio',
            'costMonth', 'savedMonth', 'activeUsers', 'disabledCount'
        );
    }

    // ── Computed: daftar user dengan token usage ──────────────
    #[Computed]
    public function usersWithUsage()
    {
        $today = now()->toDateString();
        $year  = now()->year;
        $month = now()->month;

        $query = User::query()
            ->with('tokenLimit')
            ->withSum(
                ['conversations as today_tokens' => fn($q) =>
                    $q->join('token_usages', 'conversations.id', '=', 'token_usages.conversation_id')
                      ->whereDate('token_usages.created_at', $today)
                      ->select(\DB::raw('SUM(token_usages.total_tokens)'))
                ],
                'token_usages.total_tokens'
            )
            ->withSum(
                ['conversations as month_tokens' => fn($q) =>
                    $q->join('token_usages', 'conversations.id', '=', 'token_usages.conversation_id')
                      ->whereYear('token_usages.created_at', $year)
                      ->whereMonth('token_usages.created_at', $month)
                      ->select(\DB::raw('SUM(token_usages.total_tokens)'))
                ],
                'token_usages.total_tokens'
            );

        if ($this->search) {
            $query->where(fn($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"));
        }

        $query->orderBy($this->sortBy === 'name' ? 'name' : 'id', $this->sortDir);

        return $query->paginate(12);
    }

    // ── Sort ─────────────────────────────────────────────────
    public function sort(string $col): void
    {
        if ($this->sortBy === $col) {
            $this->sortDir = $this->sortDir === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy  = $col;
            $this->sortDir = 'asc';
        }
        $this->resetPage();
    }

    // ── Modal: buka form edit limit ───────────────────────────
    public function openLimitModal(int $userId): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $user = User::findOrFail($userId);
        $limit = $user->tokenLimit ?? new TokenLimit(['user_id' => $userId, 'daily_token_limit' => 0, 'monthly_token_limit' => 0, 'is_enabled' => true]);

        $this->editingUserId   = $userId;
        $this->editingUserName = $user->name;
        $this->limitEnabled    = $limit->is_enabled;
        $this->dailyLimit      = (string) $limit->daily_token_limit;
        $this->monthlyLimit    = (string) $limit->monthly_token_limit;
        $this->limitNotes      = $limit->notes ?? '';
        $this->showLimitModal  = true;
    }

    // ── Modal: simpan ─────────────────────────────────────────
    public function saveLimitModal(): void
    {
        abort_unless(auth()->user()->is_admin, 403);

        $this->validate([
            'dailyLimit'   => 'required|integer|min:0',
            'monthlyLimit' => 'required|integer|min:0',
            'limitNotes'   => 'nullable|string|max:255',
        ]);

        TokenLimit::updateOrCreate(
            ['user_id' => $this->editingUserId],
            [
                'daily_token_limit'   => (int) $this->dailyLimit,
                'monthly_token_limit' => (int) $this->monthlyLimit,
                'is_enabled'          => $this->limitEnabled,
                'notes'               => $this->limitNotes ?: null,
            ]
        );

        $this->showLimitModal = false;
        $this->dispatch('notify', type: 'success', message: "Kuota untuk {$this->editingUserName} berhasil disimpan.");
        unset($this->usersWithUsage);
    }

    // ── Toggle enable/disable cepat ──────────────────────────
    public function toggleEnabled(int $userId): void
    {
        abort_unless(auth()->user()->is_admin, 403);
        $limit = TokenLimit::firstOrCreate(['user_id' => $userId], ['daily_token_limit' => 0, 'monthly_token_limit' => 0, 'is_enabled' => true]);
        $limit->update(['is_enabled' => ! $limit->is_enabled]);
        $state = $limit->is_enabled ? 'diaktifkan' : 'dinonaktifkan';
        $this->dispatch('notify', type: 'success', message: "Akses AI user telah {$state}.");
        unset($this->usersWithUsage);
    }

    // ── Reset kuota user ──────────────────────────────────────
    public function resetLimit(int $userId): void
    {
        abort_unless(auth()->user()->is_admin, 403);
        TokenLimit::where('user_id', $userId)->delete();
        $this->dispatch('notify', type: 'success', message: 'Kuota direset ke unlimited.');
        unset($this->usersWithUsage);
    }
}; ?>

<div class="min-h-screen bg-slate-50">

    {{-- ── Notify Toast ─────────────────────────────────────── --}}
    <div
        x-data="{ show: false, type: 'success', message: '' }"
        @notify.window="show = true; type = $event.detail[0].type; message = $event.detail[0].message; setTimeout(() => show = false, 4000)"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-end="opacity-0"
        class="fixed top-5 right-5 z-[999] max-w-sm w-full shadow-lg rounded-2xl px-4 py-3 flex items-center gap-3"
        :class="type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-800' : 'bg-red-50 border border-red-200 text-red-800'"
        style="display:none"
    >
        <span x-show="type==='success'">✅</span>
        <span x-show="type==='error'">❌</span>
        <p class="text-sm font-semibold" x-text="message"></p>
    </div>

    {{-- ── Page Header ──────────────────────────────────────── --}}
    <div class="bg-white border-b border-slate-200 sticky top-0 z-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2.5 rounded-xl bg-purple-600 text-white shadow-md shadow-purple-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div>
                    <h1 class="text-lg font-extrabold text-slate-900 leading-tight">Manajemen Token AI</h1>
                    <p class="text-xs text-slate-500">Monitor penggunaan & atur kuota per pengguna</p>
                </div>
            </div>
            <a href="{{ route('dashboard') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold transition-colors">
                ← Dashboard
            </a>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">

        {{-- ── Global Stats Cards ───────────────────────────── --}}
        @php $gs = $this->globalStats; @endphp
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">

            {{-- Token Hari Ini --}}
            <div class="col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-1">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Token Hari Ini</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ number_format($gs['totalTokensToday']) }}</p>
                <p class="text-[10px] text-slate-400">Seluruh pengguna aktif</p>
            </div>

            {{-- Token Bulan Ini --}}
            <div class="col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-1">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Token Bulan Ini</p>
                <p class="text-2xl font-extrabold text-slate-900">{{ number_format($gs['totalTokensMonth']) }}</p>
                <p class="text-[10px] text-slate-400">{{ now()->format('F Y') }}</p>
            </div>

            {{-- Cache Hit Ratio --}}
            <div class="col-span-1 bg-white rounded-2xl border border-{{ $gs['hitRatio'] >= 50 ? 'emerald' : ($gs['hitRatio'] >= 25 ? 'amber' : 'red') }}-200 p-4 shadow-sm space-y-1">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Cache Hit</p>
                <p class="text-2xl font-extrabold text-{{ $gs['hitRatio'] >= 50 ? 'emerald' : ($gs['hitRatio'] >= 25 ? 'amber' : 'red') }}-600">{{ $gs['hitRatio'] }}%</p>
                <p class="text-[10px] text-slate-400">Hemat: <span class="font-bold text-emerald-600">${{ number_format($gs['savedMonth'], 4) }}</span></p>
            </div>

            {{-- Estimasi Biaya --}}
            <div class="col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm space-y-1">
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Est. Biaya</p>
                <p class="text-2xl font-extrabold text-slate-900">${{ number_format($gs['costMonth'], 4) }}</p>
                <p class="text-[10px] text-slate-400">Bulan ini (USD)</p>
            </div>

            {{-- User Aktif & Diblokir --}}
            <div class="col-span-2 sm:col-span-3 lg:col-span-1 bg-white rounded-2xl border border-slate-200 p-4 shadow-sm flex flex-row lg:flex-col gap-4 lg:gap-1">
                <div class="flex-1 space-y-0.5">
                    <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">User Aktif</p>
                    <p class="text-2xl font-extrabold text-slate-900">{{ $gs['activeUsers'] }}</p>
                    <p class="text-[10px] text-slate-400">Hari ini</p>
                </div>
                @if(auth()->user()->is_admin && $gs['disabledCount'] > 0)
                    <div class="flex-1 space-y-0.5">
                        <p class="text-[10px] font-bold text-red-500 uppercase tracking-wider">Diblokir</p>
                        <p class="text-2xl font-extrabold text-red-600">{{ $gs['disabledCount'] }}</p>
                        <p class="text-[10px] text-slate-400">Akses nonaktif</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- ── Filter & Search ──────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row gap-3">
            <div class="relative flex-1">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Cari nama atau email pengguna..."
                    class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 bg-white"
                />
            </div>
            <div class="flex items-center gap-2">
                <button wire:click="sort('name')" class="flex items-center gap-1.5 px-3 py-2.5 rounded-xl border text-sm font-semibold {{ $sortBy === 'name' ? 'bg-purple-600 text-white border-purple-600' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50' }} transition-colors">
                    A–Z
                    @if($sortBy === 'name')
                        <span>{{ $sortDir === 'asc' ? '↑' : '↓' }}</span>
                    @endif
                </button>
            </div>
        </div>

        {{-- ── User Token Table / Cards ──────────────────────── --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">

            {{-- Desktop Table Header --}}
            <div class="hidden lg:grid grid-cols-12 gap-4 px-6 py-3 bg-slate-50 border-b border-slate-200 text-[11px] font-bold text-slate-500 uppercase tracking-wider">
                <div class="col-span-3">Pengguna</div>
                <div class="col-span-2 text-right">Token Hari Ini</div>
                <div class="col-span-2 text-right">Token Bulan Ini</div>
                <div class="col-span-2 text-center">Kuota Harian</div>
                <div class="col-span-2 text-center">Kuota Bulanan</div>
                @if(auth()->user()->is_admin)
                    <div class="col-span-1 text-center">Aksi</div>
                @endif
            </div>

            @forelse($this->usersWithUsage as $user)
                @php
                    $limit        = $user->tokenLimit;
                    $isEnabled    = $limit ? $limit->is_enabled : true;
                    $dailyLimit   = $limit?->daily_token_limit ?? 0;
                    $monthlyLimit = $limit?->monthly_token_limit ?? 0;
                    $todayTok     = (int) $user->today_tokens;
                    $monthTok     = (int) $user->month_tokens;

                    // Progress daily
                    $dailyPct     = ($dailyLimit > 0) ? min(100, round($todayTok / $dailyLimit * 100)) : null;
                    $dailyOver    = $dailyLimit > 0 && $todayTok >= $dailyLimit;
                    $monthlyPct   = ($monthlyLimit > 0) ? min(100, round($monthTok / $monthlyLimit * 100)) : null;
                    $monthlyOver  = $monthlyLimit > 0 && $monthTok >= $monthlyLimit;
                @endphp

                {{-- ── Mobile Card ───────────────────────── --}}
                <div class="lg:hidden border-b border-slate-100 last:border-0 p-4 space-y-3">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-9 h-9 rounded-xl {{ $user->is_admin ? 'bg-purple-600' : 'bg-slate-700' }} text-white flex items-center justify-center text-sm font-extrabold shrink-0">
                                {{ strtoupper(substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="font-bold text-slate-900 text-sm truncate">{{ $user->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                                @if($user->is_admin)
                                    <span class="inline-block mt-0.5 text-[9px] font-bold text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded-full">ADMIN</span>
                                @endif
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-1 rounded-full {{ $isEnabled ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $isEnabled ? 'bg-emerald-500' : 'bg-red-500' }}"></span>
                                {{ $isEnabled ? 'Aktif' : 'Diblokir' }}
                            </span>
                        </div>
                    </div>

                    {{-- Token Usage Bars --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-slate-500 font-medium">Hari Ini</span>
                                <span class="font-bold {{ $dailyOver ? 'text-red-600' : 'text-slate-800' }}">{{ number_format($todayTok) }}</span>
                            </div>
                            @if($dailyLimit > 0)
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $dailyOver ? 'bg-red-500' : ($dailyPct >= 80 ? 'bg-amber-400' : 'bg-purple-500') }}" style="width:{{ $dailyPct }}%"></div>
                                </div>
                                <p class="text-[9px] text-slate-400">Limit: {{ number_format($dailyLimit) }}</p>
                            @else
                                <p class="text-[9px] text-slate-400 italic">Unlimited</p>
                            @endif
                        </div>
                        <div class="space-y-1">
                            <div class="flex justify-between text-[10px]">
                                <span class="text-slate-500 font-medium">Bulan Ini</span>
                                <span class="font-bold {{ $monthlyOver ? 'text-red-600' : 'text-slate-800' }}">{{ number_format($monthTok) }}</span>
                            </div>
                            @if($monthlyLimit > 0)
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $monthlyOver ? 'bg-red-500' : ($monthlyPct >= 80 ? 'bg-amber-400' : 'bg-indigo-500') }}" style="width:{{ $monthlyPct }}%"></div>
                                </div>
                                <p class="text-[9px] text-slate-400">Limit: {{ number_format($monthlyLimit) }}</p>
                            @else
                                <p class="text-[9px] text-slate-400 italic">Unlimited</p>
                            @endif
                        </div>
                    </div>

                    @if(auth()->user()->is_admin)
                        <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                            <button wire:click="openLimitModal({{ $user->id }})" class="flex-1 py-1.5 rounded-lg bg-purple-50 border border-purple-200 text-purple-700 text-xs font-bold hover:bg-purple-100 transition-colors">
                                ✏ Atur Kuota
                            </button>
                            <button wire:click="toggleEnabled({{ $user->id }})" class="flex-1 py-1.5 rounded-lg {{ $isEnabled ? 'bg-red-50 border border-red-200 text-red-700 hover:bg-red-100' : 'bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100' }} text-xs font-bold transition-colors">
                                {{ $isEnabled ? '🚫 Blokir' : '✅ Aktifkan' }}
                            </button>
                            @if($limit)
                                <button wire:click="resetLimit({{ $user->id }})" class="py-1.5 px-2.5 rounded-lg bg-slate-100 border border-slate-200 text-slate-600 text-xs font-bold hover:bg-slate-200 transition-colors" title="Reset ke unlimited">
                                    ↺
                                </button>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- ── Desktop Row ─────────────────────────── --}}
                <div class="hidden lg:grid grid-cols-12 gap-4 items-center px-6 py-4 border-b border-slate-100 last:border-0 hover:bg-slate-50/50 transition-colors">

                    {{-- User Info --}}
                    <div class="col-span-3 flex items-center gap-3 min-w-0">
                        <div class="w-9 h-9 rounded-xl {{ $user->is_admin ? 'bg-purple-600' : 'bg-slate-700' }} text-white flex items-center justify-center text-sm font-extrabold shrink-0">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <p class="font-bold text-slate-900 text-sm truncate">{{ $user->name }}</p>
                                @if($user->is_admin)
                                    <span class="text-[9px] font-bold text-purple-700 bg-purple-100 px-1.5 py-0.5 rounded-full shrink-0">ADMIN</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-400 truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    {{-- Today Tokens --}}
                    <div class="col-span-2 text-right">
                        <p class="font-bold text-sm {{ $dailyOver ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($todayTok) }}</p>
                        <p class="text-[10px] text-slate-400">token</p>
                    </div>

                    {{-- Month Tokens --}}
                    <div class="col-span-2 text-right">
                        <p class="font-bold text-sm {{ $monthlyOver ? 'text-red-600' : 'text-slate-900' }}">{{ number_format($monthTok) }}</p>
                        <p class="text-[10px] text-slate-400">token</p>
                    </div>

                    {{-- Daily Quota --}}
                    <div class="col-span-2 space-y-1.5 text-center">
                        @if($dailyLimit > 0)
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($todayTok) }}</span>
                                <span>{{ number_format($dailyLimit) }}</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-2 rounded-full {{ $dailyOver ? 'bg-red-500' : ($dailyPct >= 80 ? 'bg-amber-400' : 'bg-purple-500') }}" style="width:{{ $dailyPct }}%"></div>
                            </div>
                            <p class="text-[10px] font-bold {{ $dailyOver ? 'text-red-600' : 'text-slate-600' }}">{{ $dailyPct }}%</p>
                        @else
                            <span class="text-xs text-slate-400 italic">Unlimited</span>
                        @endif
                    </div>

                    {{-- Monthly Quota --}}
                    <div class="col-span-2 space-y-1.5 text-center">
                        @if($monthlyLimit > 0)
                            <div class="flex justify-between text-[10px] text-slate-500">
                                <span>{{ number_format($monthTok) }}</span>
                                <span>{{ number_format($monthlyLimit) }}</span>
                            </div>
                            <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                                <div class="h-2 rounded-full {{ $monthlyOver ? 'bg-red-500' : ($monthlyPct >= 80 ? 'bg-amber-400' : 'bg-indigo-500') }}" style="width:{{ $monthlyPct }}%"></div>
                            </div>
                            <p class="text-[10px] font-bold {{ $monthlyOver ? 'text-red-600' : 'text-slate-600' }}">{{ $monthlyPct }}%</p>
                        @else
                            <span class="text-xs text-slate-400 italic">Unlimited</span>
                        @endif
                    </div>

                    {{-- Aksi (admin only) --}}
                    @if(auth()->user()->is_admin)
                        <div class="col-span-1 flex items-center justify-center gap-1">
                            {{-- Status Badge --}}
                            <span class="inline-block w-2 h-2 rounded-full {{ $isEnabled ? 'bg-emerald-500' : 'bg-red-500' }}" title="{{ $isEnabled ? 'Aktif' : 'Diblokir' }}"></span>

                            {{-- Menu Dropdown --}}
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500 hover:text-slate-800 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                                </button>
                                <div
                                    x-show="open" @click.outside="open = false"
                                    x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute right-0 top-full mt-1 w-44 bg-white rounded-xl border border-slate-200 shadow-lg z-30 py-1 text-sm"
                                    style="display:none"
                                >
                                    <button @click="open=false" wire:click="openLimitModal({{ $user->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-purple-50 text-purple-700 font-semibold flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        Atur Kuota
                                    </button>
                                    <button @click="open=false" wire:click="toggleEnabled({{ $user->id }})"
                                        class="w-full text-left px-3 py-2 hover:bg-slate-50 {{ $isEnabled ? 'text-red-700' : 'text-emerald-700' }} font-semibold flex items-center gap-2">
                                        @if($isEnabled)
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                                            Blokir Akses
                                        @else
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                            Aktifkan Akses
                                        @endif
                                    </button>
                                    @if($limit)
                                        <hr class="my-1 border-slate-100">
                                        <button @click="open=false" wire:click="resetLimit({{ $user->id }})"
                                            class="w-full text-left px-3 py-2 hover:bg-slate-50 text-slate-600 font-semibold flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                                            Reset Unlimited
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="py-16 text-center space-y-2">
                    <p class="text-4xl">🔍</p>
                    <p class="font-semibold text-slate-700">Tidak ada pengguna ditemukan</p>
                    <p class="text-sm text-slate-400">Coba ubah kata kunci pencarian</p>
                </div>
            @endforelse

        </div>

        {{-- Pagination --}}
        <div class="pb-2">
            {{ $this->usersWithUsage->links() }}
        </div>

    </div>{{-- end max-w --}}

    {{-- ══════════════════════════════════════════════════════ --}}
    {{-- MODAL: Edit Kuota Token                               --}}
    {{-- ══════════════════════════════════════════════════════ --}}
    @if($showLimitModal)
        <div class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-0 sm:p-4 bg-slate-900/60 backdrop-blur-sm" x-data x-on:keydown.escape.window="$wire.set('showLimitModal', false)">
            <div class="w-full sm:max-w-md bg-white sm:rounded-3xl rounded-t-3xl shadow-2xl border border-slate-100 overflow-hidden">

                {{-- Modal Header --}}
                <div class="px-6 pt-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="p-2 rounded-xl bg-purple-100">
                            <svg class="w-5 h-5 text-purple-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                        </div>
                        <div>
                            <h3 class="font-extrabold text-slate-900 text-base">Atur Kuota Token</h3>
                            <p class="text-xs text-slate-500">{{ $editingUserName }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('showLimitModal', false)" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-5 space-y-5">

                    {{-- Enable / Disable toggle --}}
                    <div class="flex items-center justify-between p-4 rounded-2xl {{ $limitEnabled ? 'bg-emerald-50 border border-emerald-200' : 'bg-red-50 border border-red-200' }}">
                        <div>
                            <p class="font-bold text-sm {{ $limitEnabled ? 'text-emerald-800' : 'text-red-800' }}">Akses AI Chat</p>
                            <p class="text-xs {{ $limitEnabled ? 'text-emerald-600' : 'text-red-600' }}">{{ $limitEnabled ? 'User dapat menggunakan AI' : 'Akses AI diblokir total' }}</p>
                        </div>
                        <button wire:click="$set('limitEnabled', !$limitEnabled)"
                            class="relative inline-flex h-7 w-12 items-center rounded-full transition-colors {{ $limitEnabled ? 'bg-emerald-500' : 'bg-red-400' }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform {{ $limitEnabled ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </div>

                    {{-- Daily Limit --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">
                            Kuota Harian (token)
                            <span class="font-normal text-slate-400 text-xs ml-1">0 = unlimited</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number" min="0" step="1000"
                                wire:model="dailyLimit"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 text-sm font-semibold"
                                placeholder="Contoh: 50000"
                            />
                            @if((int)$dailyLimit > 0)
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">
                                    ≈ ${{ number_format((int)$dailyLimit * 0.14 / 1_000_000, 5) }}/hari
                                </span>
                            @endif
                        </div>
                        <p class="text-[11px] text-slate-400">Contoh preset: 10K=hemat, 50K=normal, 200K=intensif</p>
                    </div>

                    {{-- Monthly Limit --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">
                            Kuota Bulanan (token)
                            <span class="font-normal text-slate-400 text-xs ml-1">0 = unlimited</span>
                        </label>
                        <div class="relative">
                            <input
                                type="number" min="0" step="10000"
                                wire:model="monthlyLimit"
                                class="w-full px-4 py-3 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 text-sm font-semibold"
                                placeholder="Contoh: 1000000"
                            />
                            @if((int)$monthlyLimit > 0)
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-slate-400 font-medium">
                                    ≈ ${{ number_format((int)$monthlyLimit * 0.14 / 1_000_000, 4) }}/bln
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- Notes --}}
                    <div class="space-y-1.5">
                        <label class="block text-sm font-bold text-slate-700">Catatan Admin <span class="font-normal text-slate-400">(opsional)</span></label>
                        <input
                            type="text" wire:model="limitNotes"
                            placeholder="Mis: paket premium, trial 30 hari..."
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:outline-none focus:ring-2 focus:ring-purple-500/30 focus:border-purple-500 text-sm"
                        />
                    </div>

                    {{-- Preset Buttons --}}
                    <div class="space-y-1.5">
                        <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Preset Cepat</p>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" wire:click="$set('dailyLimit', '10000')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition-colors">10K/hari</button>
                            <button type="button" wire:click="$set('dailyLimit', '50000')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition-colors">50K/hari</button>
                            <button type="button" wire:click="$set('dailyLimit', '100000')" class="px-3 py-1.5 rounded-lg bg-slate-100 text-slate-700 text-xs font-semibold hover:bg-slate-200 transition-colors">100K/hari</button>
                            <button type="button" wire:click="$set('monthlyLimit', '500000')" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition-colors">500K/bln</button>
                            <button type="button" wire:click="$set('monthlyLimit', '1000000')" class="px-3 py-1.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-semibold hover:bg-indigo-100 transition-colors">1M/bln</button>
                        </div>
                    </div>
                </div>

                {{-- Modal Footer --}}
                <div class="px-6 pb-6 flex gap-3">
                    <button wire:click="$set('showLimitModal', false)"
                        class="flex-1 py-3 rounded-2xl border border-slate-300 text-slate-700 font-bold text-sm hover:bg-slate-50 transition-colors">
                        Batal
                    </button>
                    <button wire:click="saveLimitModal"
                        class="flex-1 py-3 rounded-2xl bg-purple-600 text-white font-bold text-sm hover:bg-purple-700 transition-colors shadow-md shadow-purple-500/20">
                        <span wire:loading.remove wire:target="saveLimitModal">Simpan Kuota</span>
                        <span wire:loading wire:target="saveLimitModal">Menyimpan...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>
