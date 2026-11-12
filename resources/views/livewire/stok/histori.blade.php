<?php

use App\Models\{Ledger, LedgerItem, User};
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
        return Ledger::with(['product', 'location', 'customer', 'supplier', 'user', 'updater', 'items.product', 'items.location'])
            ->where(fn ($query) => $query->whereNotNull('stock_movement')->orWhereHas('items'))
            ->when($this->search, fn ($query) => $query->where(fn ($searchQuery) => $searchQuery
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")
                ->orWhereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$this->search}%"))
                ->orWhereHas('items', fn ($itemsQuery) => $itemsQuery->where('product_name', 'like', "%{$this->search}%"))))
            ->when($this->filterDir, fn ($query) => $query->where(fn ($movementQuery) => $movementQuery
                ->where('stock_movement', $this->filterDir)
                ->orWhereHas('items', fn ($itemsQuery) => $itemsQuery->whereHas('ledger', fn ($ledgerQuery) =>
                    $ledgerQuery->where('type', $this->filterDir === 'in' ? 'expense' : 'income')))))
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
        $legacy = Ledger::where('stock_movement', 'in')
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->sum('quantity');
        $items = LedgerItem::whereHas('ledger', fn ($query) => $query->where('type', 'expense')
            ->when($this->filterBulan, fn ($ledgerQuery) => $ledgerQuery->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan])))
            ->sum('quantity');

        return (int) $legacy + (int) $items;
    }

    public function getTotalKeluarProperty(): int
    {
        $legacy = Ledger::where('stock_movement', 'out')
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->sum('quantity');
        $items = LedgerItem::whereHas('ledger', fn ($query) => $query->where('type', 'income')
            ->when($this->filterBulan, fn ($ledgerQuery) => $ledgerQuery->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan])))
            ->sum('quantity');

        return (int) $legacy + (int) $items;
    }

    public function updatedSearch():      void { $this->resetPage(); }
    public function updatedFilterDir():   void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
    public function updatedFilterUser():  void { $this->resetPage(); }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-lg bg-white border border-[#dce1de]/80 text-[#52646a] hover:text-[#315b50] hover:border-[#dce1de] shadow-none transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Audit Log</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Histori Mutasi Stok
                </h1>
                <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Semua pergerakan stok barang masuk & keluar beserta riwayat penginput.</p>
            </div>
        </div>
    </div>

    {{-- ── Mini KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none flex items-center gap-4">
            <div class="w-11 h-11 bg-[#f5f6f3] text-[#315b50] rounded-lg flex items-center justify-center shrink-0 border border-[#dce1de]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-[#71817f] uppercase tracking-wider">Stok Masuk</p>
                <p class="text-lg sm:text-2xl font-extrabold text-[#315b50] mt-0.5">{{ number_format($this->totalMasuk) }} pcs</p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none flex items-center gap-4">
            <div class="w-11 h-11 bg-[#f7f1e4] text-[#5e4820] rounded-lg flex items-center justify-center shrink-0 border border-[#ddcba7]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-[#71817f] uppercase tracking-wider">Stok Keluar</p>
                <p class="text-lg sm:text-2xl font-extrabold text-[#5e4820] mt-0.5">{{ number_format($this->totalKeluar) }} pcs</p>
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#71817f]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari judul transaksi / produk..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] transition-all shadow-none" />
        </div>

        <select wire:model.live="filterDir"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Arah</option>
            <option value="in">Masuk</option>
            <option value="out">Keluar</option>
        </select>

        <select wire:model.live="filterUser"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Pengguna</option>
            @foreach($this->users as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
            @endforeach
        </select>

        <input wire:model.live="filterBulan" type="month"
               class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none transition-all" />
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse($this->movements as $l)
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 shadow-none relative overflow-hidden space-y-3">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="font-extrabold text-[#172c35] text-sm truncate">{{ $l->product?->name ?? ($l->items->first()?->product_name ?? $l->title) }}</p>
                    <p class="text-[11px] font-medium text-[#71817f] mt-0.5">{{ $l->date->format('d M Y') }}</p>
                </div>
                @if($l->items->isNotEmpty())
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-extrabold {{ $l->type === 'expense' ? 'bg-[#f5f6f3] text-[#315b50] border border-[#dce1de]' : 'bg-[#f7f1e4] text-[#5e4820] border border-[#ddcba7]' }}">
                        {{ $l->type === 'expense' ? '+' : '-' }}{{ $l->items->sum('quantity') }} unit
                    </span>
                @else
                    <span class="shrink-0 px-2.5 py-1 rounded-full text-xs font-extrabold {{ $l->stock_movement === 'in' ? 'bg-[#f5f6f3] text-[#315b50] border border-[#dce1de]' : 'bg-[#f7f1e4] text-[#5e4820] border border-[#ddcba7]' }}">
                        {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ $l->quantity }} pcs
                    </span>
                @endif
            </div>

            @if($l->items->isNotEmpty())
                <div class="space-y-1">
                    @foreach($l->items as $item)
                        <p class="text-xs text-[#64747a]">{{ $item->product_name }} × {{ $item->quantity }} · {{ $item->location_name }}</p>
                    @endforeach
                </div>
            @elseif($l->location)
            <p class="text-xs text-[#64747a] font-medium flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-[#71817f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                {{ $l->location->name }}
            </p>
            @endif

            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-[#e6eae7] text-[10px] font-extrabold">
                @if($l->user)
                <span class="px-2.5 py-1 rounded-lg bg-[#f5f6f3] text-[#17343d] border border-[#dce1de] flex items-center gap-1">
                    <svg class="w-3 h-3 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    {{ $l->user->name }}
                </span>
                @endif
                @if($l->supplier)
                <span class="px-2.5 py-1 rounded-lg bg-[#f5f6f3] text-[#17343d] border border-[#dce1de] flex items-center gap-1">
                    <svg class="w-3 h-3 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    {{ $l->supplier->name }}
                </span>
                @endif
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-8 text-center shadow-none">
            <p class="text-[#71817f] text-xs sm:text-sm font-semibold">Belum ada mutasi stok yang ditemukan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#f5f6f3]/80 border-b border-[#dce1de]/80 text-[11px] font-extrabold text-[#64747a] uppercase tracking-wider">
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
            <tbody class="divide-y border-[#e6eae7] text-xs sm:text-sm font-medium text-[#344950]">
                @forelse($this->movements as $l)
                <tr class="hover:bg-[#f5f6f3]/30 transition-colors">
                    <td class="px-6 py-4 text-[#71817f] font-semibold whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        @if($l->items->isNotEmpty())
                            <p class="font-extrabold text-[#172c35]">
                                @foreach($l->items as $item)
                                    {{ $item->product_name }} × {{ $item->quantity }}@if(!$loop->last); @endif
                                @endforeach
                            </p>
                            <p class="text-xs text-[#71817f] font-medium mt-0.5">{{ $l->title }}</p>
                        @elseif($l->product)
                            <p class="font-extrabold text-[#172c35]">{{ $l->product->name }}</p>
                            <p class="text-xs text-[#71817f] font-medium mt-0.5">{{ $l->title }}</p>
                        @else
                            <p class="font-extrabold text-[#172c35]">{{ $l->title }}</p>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-[#64747a] font-medium text-xs">
                        @if($l->items->isNotEmpty())
                            @foreach($l->items as $item)
                                <p>{{ $item->location_name }}</p>
                            @endforeach
                        @else
                            {{ $l->location?->name ?? '—' }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ ($l->items->isNotEmpty() ? $l->type === 'expense' : $l->stock_movement === 'in') ? 'bg-[#f5f6f3] text-[#315b50] border border-[#dce1de]' : 'bg-[#f7f1e4] text-[#5e4820] border border-[#ddcba7]' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                @if($l->items->isNotEmpty() ? $l->type === 'expense' : $l->stock_movement === 'in')
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                @else
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                @endif
                            </svg>
                            {{ ($l->items->isNotEmpty() ? $l->type === 'expense' : $l->stock_movement === 'in') ? 'Masuk' : 'Keluar' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $l->stock_movement === 'in' ? 'text-[#315b50]' : 'text-[#5e4820]' }}">
                        @if($l->items->isNotEmpty())
                            @foreach($l->items as $item)
                                <p>{{ $l->type === 'expense' ? '+' : '-' }}{{ number_format($item->quantity) }}</p>
                            @endforeach
                        @else
                            {{ $l->stock_movement === 'in' ? '+' : '-' }}{{ number_format($l->quantity) }}
                        @endif
                    </td>
                    <td class="px-6 py-4 text-xs font-semibold text-[#52646a]">
                        @if($l->supplier)<p class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> {{ $l->supplier->name }}</p>@endif
                        @if($l->customer)<p class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-[#6b5226]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> {{ $l->customer->name }}</p>@endif
                        @if(!$l->supplier && !$l->customer)<span class="text-[#71817f]">—</span>@endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->user)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-[#f5f6f3] text-[#315b50] font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($l->user->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-[#344950]">{{ $l->user->name }}</p>
                                <p class="text-[10px] text-[#71817f] font-medium">{{ $l->created_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-[#71817f]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->updater && $l->updated_by !== $l->user_id)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-lg bg-[#e8efeb] text-[#315b50] font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($l->updater->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-[#344950]">{{ $l->updater->name }}</p>
                                <p class="text-[10px] text-[#71817f] font-medium">{{ $l->updated_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @else
                        <span class="text-xs text-[#71817f]">—</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-[#71817f] text-xs sm:text-sm font-semibold">Belum ada mutasi stok untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2">
        {{ $this->movements->links() }}
    </div>
</div>
