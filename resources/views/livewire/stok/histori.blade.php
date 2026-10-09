<?php

use App\Models\{Ledger, User};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterDir   = '';   // 'in' | 'out'
    public string $filterBulan = '';
    public string $filterUser  = '';

    public function mount(): void
    {
        $this->authorize('view-stok');
    }

    public function getMovementsProperty()
    {
        return Ledger::with(['product', 'location', 'customer', 'supplier', 'user', 'updater'])
            ->whereNotNull('stock_movement')
            ->when($this->search,      fn($q) => $q->where('title', 'like', "%{$this->search}%")
                                                    ->orWhereHas('product', fn($r) => $r->where('name', 'like', "%{$this->search}%")))
            ->when($this->filterDir,   fn($q) => $q->where('stock_movement', $this->filterDir))
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->when($this->filterUser,  fn($q) => $q->where('user_id', $this->filterUser))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20);
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function getTotalMasukProperty(): int
    {
        return (int) Ledger::whereNotNull('stock_movement')->where('stock_movement', 'in')
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->sum('quantity');
    }

    public function getTotalKeluarProperty(): int
    {
        return (int) Ledger::whereNotNull('stock_movement')->where('stock_movement', 'out')
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->sum('quantity');
    }

    public function updatedSearch():      void { $this->resetPage(); }
    public function updatedFilterDir():   void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
    public function updatedFilterUser():  void { $this->resetPage(); }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Audit Log</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Histori Mutasi Stok
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Semua pergerakan stok barang masuk & keluar beserta riwayat penginput.</p>
            </div>
        </div>
    </div>

    {{-- ── Mini KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shrink-0 border border-blue-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Masuk</p>
                <p class="text-lg sm:text-2xl font-extrabold text-blue-600 mt-0.5">{{ number_format($this->totalMasuk) }} pcs</p>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm flex items-center gap-4">
            <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shrink-0 border border-amber-100">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Stok Keluar</p>
                <p class="text-lg sm:text-2xl font-extrabold text-amber-600 mt-0.5">{{ number_format($this->totalKeluar) }} pcs</p>
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari judul transaksi / produk..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
        </div>

        <select wire:model.live="filterDir"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Arah</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
        </select>

        <select wire:model.live="filterUser"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Pengguna</option>
            @foreach($this->users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        <input wire:model.live="filterBulan" type="month"
               class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all" />
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse($this->movements as $l)
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm relative overflow-hidden space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 text-sm truncate">{{ $l->product?->name ?? $l->title }}</p>
                    <p class="text-[11px] font-medium text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                </div>
                <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-extrabold {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                    {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ $l->quantity }} pcs
                </span>
            </div>

            @if($l->location)
            <p class="text-xs text-slate-500 font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $l->location->name }}
            </p>
            @endif

            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-slate-100 text-[10px] font-extrabold">
                @if($l->user)
                <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 flex items-center gap-1">
                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    {{ $l->user->name }}
                </span>
                @endif
                @if($l->supplier)
                <span class="px-2.5 py-1 rounded-lg bg-sky-50 text-sky-700 border border-sky-100 flex items-center gap-1">
                    <svg class="w-3 h-3 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    {{ $l->supplier->name }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-xs sm:text-sm font-semibold">Belum ada mutasi stok yang ditemukan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Detail Produk</th>
                    <th class="px-6 py-4">Lokasi</th>
                    <th class="px-6 py-4 text-center">Arah</th>
                    <th class="px-6 py-4 text-right">Qty</th>
                    <th class="px-6 py-4">Entitas Terkait</th>
                    <th class="px-6 py-4">Diinput Oleh</th>
                    <th class="px-6 py-4">Diedit Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse($this->movements as $l)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4 text-slate-400 font-semibold whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        @if($l->product)
                            <p class="font-extrabold text-slate-900">{{ $l->product->name }}</p>
                            <p class="text-xs text-slate-400 font-medium mt-0.5">{{ $l->title }}</p>
                        @else
                            <p class="font-extrabold text-slate-900">{{ $l->title }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-slate-500 font-medium text-xs">
                        {{ $l->location?->name ?? '—' }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($l->stock_movement === 'in')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                @endif
                            </svg>
                            {{ $l->stock_movement === 'in' ? 'Masuk' : 'Keluar' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $l->stock_movement === 'in' ? 'text-blue-600' : 'text-amber-600' }}">
                        {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ number_format($l->quantity) }}
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-slate-600">
                        @if($l->supplier)<p class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> {{ $l->supplier->name }}</p>@endif
                        @if($l->customer)<p class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> {{ $l->customer->name }}</p>@endif
                        @if(!$l->supplier && !$l->customer)<span class="text-slate-400">—</span>@endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->user)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl bg-blue-50 text-blue-600 font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($l->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ $l->user->name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">{{ $l->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->updater && $l->updated_by !== $l->user_id)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-600 font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($l->updater->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ $l->updater->name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">{{ $l->updated_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-xs sm:text-sm font-semibold">Belum ada mutasi stok untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2">
        {{ $this->movements->links() }}
    </div>
</div>
