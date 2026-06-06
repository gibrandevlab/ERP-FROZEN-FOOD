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
        $this->authorize('view-products');
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

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('stok.index') }}" wire:navigate
                   class="text-slate-400 hover:text-blue-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-semibold text-blue-600">Histori</span>
            </div>
            <h1 class="text-xl font-extrabold" style="color:#1E293B;">Histori Mutasi Stok</h1>
            <p class="text-xs text-slate-500 mt-0.5">Semua pergerakan stok beserta info penginput</p>
        </div>
    </div>

    {{-- ── Mini KPI ──────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Stok Masuk</p>
                    <p class="text-base font-bold text-blue-600">{{ number_format($this->totalMasuk) }} pcs</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Stok Keluar</p>
                    <p class="text-base font-bold text-orange-500">{{ number_format($this->totalKeluar) }} pcs</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari judul / produk..."
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
        <select wire:model.live="filterDir"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
            <option value="">Semua Arah</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
        </select>
        <select wire:model.live="filterUser"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
            <option value="">Semua Pengguna</option>
            @foreach($this->users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>
        <input wire:model.live="filterBulan" type="month"
               class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm" />
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden pb-4">
        @forelse($this->movements as $l)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $l->stock_movement === 'in' ? 'bg-blue-400' : 'bg-orange-400' }}"></div>
            <div class="pl-2 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800 text-sm truncate">{{ $l->product?->name ?? $l->title }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                    </div>
                    <span class="flex-shrink-0 px-2.5 py-1 rounded-lg text-xs font-bold {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                        {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ $l->quantity }} pcs
                    </span>
                </div>
                @if($l->location)
                <p class="text-[10px] text-slate-400">📍 {{ $l->location->name }}</p>
                @endif
                <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-50 text-[10px] font-semibold">
                    @if($l->user)
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">📝 {{ $l->user->name }}</span>
                    @endif
                    @if($l->supplier)
                    <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-600">🏭 {{ $l->supplier->name }}</span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm font-semibold">Belum ada mutasi stok untuk filter ini.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-x-auto"
         style="box-shadow:0 4px 40px rgba(0,0,0,0.05),0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full min-w-[900px] text-sm">
            <thead style="background:linear-gradient(135deg,rgba(248,250,252,0.95),rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Produk</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Lokasi</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Arah</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Qty</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Supplier / Pelanggan</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Diinput Oleh</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Diedit Oleh</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->movements as $l)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-5 py-4 text-slate-400 whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-5 py-4">
                        @if($l->product)
                        <p class="font-semibold text-slate-800">{{ $l->product->name }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $l->title }}</p>
                        @else
                        <p class="font-semibold text-slate-800">{{ $l->title }}</p>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-slate-500 text-xs">
                        {{ $l->location?->name ?? '—' }}
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                            {{ $l->stock_movement === 'in' ? '▲ Masuk' : '▼ Keluar' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right font-bold {{ $l->stock_movement === 'in' ? 'text-blue-600' : 'text-orange-500' }}">
                        {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ number_format($l->quantity) }}
                    </td>
                    <td class="px-5 py-4 text-xs text-slate-500">
                        @if($l->supplier)<p>🏭 {{ $l->supplier->name }}</p>@endif
                        @if($l->customer)<p>👤 {{ $l->customer->name }}</p>@endif
                        @if(!$l->supplier && !$l->customer)<span class="text-slate-400">—</span>@endif
                    </td>
                    <td class="px-5 py-4">
                        @if($l->user)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($l->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">{{ $l->user->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $l->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4">
                        @if($l->updater && $l->updated_by !== $l->user_id)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($l->updater->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">{{ $l->updater->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $l->updated_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-12 text-center text-slate-400 text-sm">Belum ada mutasi stok untuk filter ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sticky bottom-4 sm:static mt-6 sm:mt-2 z-40">
        <div class="bg-white/95 sm:bg-transparent backdrop-blur-md sm:backdrop-blur-none border sm:border-0 border-slate-200/60 rounded-2xl sm:rounded-none p-2 sm:p-0 shadow-2xl sm:shadow-none max-w-sm mx-auto sm:max-w-none transition-all">
            {{ $this->movements->links() }}
        </div>
    </div>

</div>
