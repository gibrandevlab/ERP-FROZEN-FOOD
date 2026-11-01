<?php

use App\Models\{Ledger, Product};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterType  = '';
    public string $filterBulan = '';

    public function mount(): void
    {
        $this->authorize('view-pembukuan');
        $this->filterBulan = now()->format('Y-m');
    }

    public function getLedgersProperty()
    {
        return Ledger::with(['product', 'location'])
            ->when($this->search, fn($q) => $q->where(fn($searchQuery) => $searchQuery
                ->where('title', 'like', "%{$this->search}%")
                ->orWhere('reference', 'like', "%{$this->search}%")))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->orderByDesc('date')->orderByDesc('id')->paginate(15);
    }

    public function getTotalPemasukanProperty(): string
    {
        return number_format(
            Ledger::income()->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))->sum('amount'),
            0, ',', '.'
        );
    }

    public function getTotalPengeluaranProperty(): string
    {
        return number_format(
            Ledger::expense()->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))->sum('amount'),
            0, ',', '.'
        );
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-pembukuan');
        DB::transaction(fn () => Ledger::findOrFail($id)->delete());
        session()->flash('success', 'Transaksi berhasil dihapus.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Flash Messages ── --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="rounded-lg border border-[#c9dad3] bg-[#e8efeb] p-3.5 text-xs sm:text-sm text-[#24483f] shadow-none flex items-center justify-between gap-3">
            <span class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-[#2f6b5c] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </span>
            <button type="button" @click="show = false" class="text-[#2f6b5c] hover:text-[#24483f] font-bold p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#eef2ee] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#dce1de]/80 text-[#315b50] rounded-lg">Keuangan & Kas</span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Pembukuan
            </h1>
            <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Catatan transaksi arus kas masuk dan keluar secara real-time.</p>
        </div>

        <div class="flex items-center gap-2 shrink-0 self-start sm:self-auto">
            <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate @click="playClick()"
               class="btn-sound inline-flex items-center justify-center gap-2 px-4 py-3 rounded-lg text-xs sm:text-sm font-bold text-[#344950] bg-white border border-[#dce1de]/80 hover:bg-[#f5f6f3] transition-all shadow-none">
                <svg class="w-4 h-4 text-[#64747a]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="hidden sm:inline">Ringkasan</span>
            </a>

            <a href="{{ route('pembukuan.tambah') }}" wire:navigate @click="playClick()"
               class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs sm:text-sm font-extrabold text-white bg-[#244e49] hover:bg-[#173d38] shadow-none shadow-[#244e49]/15 transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Catat Transaksi</span>
            </a>
        </div>
    </div>

    {{-- ── Mini KPI Cards ── --}}
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none flex items-center gap-4">
            <div class="w-11 h-11 bg-[#e8efeb] text-[#2f6b5c] rounded-lg flex items-center justify-center shrink-0 border border-[#d3e0da]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-[#71817f] uppercase tracking-wider">Total Pemasukan</p>
                <p class="text-base sm:text-2xl font-extrabold text-[#2f6b5c] mt-0.5">Rp {{ $this->totalPemasukan }}</p>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none flex items-center gap-4">
            <div class="w-11 h-11 bg-[#f7eceb] text-[#8f4a4a] rounded-lg flex items-center justify-center shrink-0 border border-[#ead6d4]">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
            </div>
            <div>
                <p class="text-xs font-bold text-[#71817f] uppercase tracking-wider">Total Pengeluaran</p>
                <p class="text-base sm:text-2xl font-extrabold text-[#8f4a4a] mt-0.5">Rp {{ $this->totalPengeluaran }}</p>
            </div>
        </div>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#71817f]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari judul atau referensi..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] transition-all shadow-none" />
        </div>

        <select wire:model.live="filterType"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Tipe</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
        </select>

        <input wire:model.live="filterBulan" type="month"
               class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] shadow-none transition-all" />
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse($this->ledgers as $l)
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 shadow-none relative overflow-hidden space-y-3">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $l->type === 'income' ? 'bg-[#2f6b5c]' : 'bg-[#9c5252]' }}"></div>

            <div class="flex items-start justify-between gap-3 pl-2">
                <div class="min-w-0 flex-1">
                    <p class="font-extrabold text-[#172c35] text-sm truncate">{{ $l->title }}</p>
                    <p class="text-[11px] font-semibold text-[#71817f] mt-0.5">{{ $l->date->format('d M Y') }}</p>
                    @if($l->product)
                        <p class="text-[10px] font-extrabold text-[#315b50] mt-1.5 inline-flex items-center gap-1 bg-[#e8efeb] px-2 py-0.5 rounded-lg border border-[#dce1de]">
                            <svg class="w-3 h-3 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            {{ $l->product->name }}
                        </p>
                    @endif
                </div>
                <div class="text-right shrink-0">
                    <p class="text-sm font-extrabold {{ $l->type === 'income' ? 'text-[#2f6b5c]' : 'text-[#8f4a4a]' }}">
                        {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                    </p>
                    @if($l->stock_movement && $l->quantity)
                        <p class="text-[10px] font-extrabold mt-1.5 {{ $l->stock_movement === 'in' ? 'text-[#315b50] bg-[#e8efeb] border-[#dce1de]' : 'text-[#8a6a2f] bg-[#f7f1e4] border-[#ebdfc6]' }} px-2 py-0.5 rounded-lg border inline-block">
                            {{ $l->stock_movement === 'in' ? 'Masuk' : 'Keluar' }} {{ $l->quantity }} pcs
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-[#e6eae7] pl-2">
                @if($l->reference)
                    <p class="text-[10px] text-[#71817f] font-mono font-bold bg-[#f5f6f3] px-2 py-1 rounded-md border border-[#e6eae7]">Ref: {{ $l->reference }}</p>
                @else
                    <div></div>
                @endif

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound p-1.5 rounded-md bg-[#e8efeb] text-[#315b50] border border-[#dce1de] hover:bg-[#dce1de] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus catatan '{{ $l->title }}'?" @click="playDanger()"
                            class="btn-sound p-1.5 rounded-md bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4] hover:bg-[#ead6d4] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-8 text-center shadow-none">
            <p class="text-[#71817f] text-xs sm:text-sm font-semibold">Belum ada catatan transaksi.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#f5f6f3]/80 border-b border-[#dce1de]/80 text-[11px] font-extrabold text-[#64747a] uppercase tracking-wider">
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Keterangan</th>
                    <th class="px-6 py-4">Mutasi Stok</th>
                    <th class="px-6 py-4 text-center">Keuangan</th>
                    <th class="px-6 py-4 text-right">Nominal</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6eae7] text-xs sm:text-sm font-medium text-[#344950]">
                @forelse($this->ledgers as $l)
                <tr class="hover:bg-[#e8efeb]/30 transition-colors">
                    <td class="px-6 py-4 text-[#71817f] font-semibold whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        <p class="font-extrabold text-[#172c35]">{{ $l->title }}</p>
                        @if($l->reference)<p class="text-xs text-[#71817f] font-mono mt-0.5">Ref: {{ $l->reference }}</p>@endif
                        @if($l->product)<p class="text-xs font-bold text-[#315b50] mt-0.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 text-[#527c70]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> {{ $l->product->name }}</p>@endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->stock_movement)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $l->stock_movement === 'in' ? 'bg-[#e8efeb] text-[#315b50] border border-[#dce1de]' : 'bg-[#f7f1e4] text-[#8a6a2f] border border-[#ebdfc6]' }}">
                                    {{ $l->stock_movement === 'in' ? '+ Masuk' : '- Keluar' }}
                                </span>
                                <span class="text-xs font-extrabold text-[#344950]">{{ $l->quantity }} pcs</span>
                            </div>
                            @if($l->location)
                                <p class="text-xs text-[#71817f] font-medium mt-1 flex items-center gap-1"><svg class="w-3 h-3 text-[#71817f]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> {{ $l->location->name }}</p>
                            @endif
                        @else
                            <span class="text-xs text-[#71817f]">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $l->type==='income' ? 'bg-[#e8efeb] text-[#2f6b5c] border border-[#d3e0da]' : 'bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4]' }}">
                            {{ $l->type==='income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $l->type==='income' ? 'text-[#2f6b5c]' : 'text-[#8f4a4a]' }}">
                        {{ $l->type==='income' ? '+' : '-' }} Rp {{ number_format($l->amount,0,',','.') }}
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound p-2 rounded-md bg-[#e8efeb] text-[#315b50] border border-[#dce1de] hover:bg-[#dce1de] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus catatan '{{ $l->title }}'?" @click="playDanger()"
                                    class="btn-sound p-2 rounded-md bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4] hover:bg-[#ead6d4] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-[#71817f] text-xs sm:text-sm font-semibold">Belum ada catatan transaksi untuk periode ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Container Pagination Seragam ── --}}
    <div class="pt-2 custom-pagination">
        <style>
            .custom-pagination nav[role="navigation"] { display: flex; align-items: center; justify-between; flex-wrap: wrap; gap: 0.75rem; }
            .custom-pagination nav span[aria-current="page"] > span { background-color: #244e49 !important; color: #ffffff !important; border-color: #244e49 !important; border-radius: 0.875rem !important; font-weight: 800 !important; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important; }
            .custom-pagination nav a, .custom-pagination nav span[aria-disabled="true"] > span { border-radius: 0.875rem !important; font-size: 0.75rem !important; font-weight: 700 !important; border-color: #dce1de !important; color: #52646a !important; padding: 0.5rem 0.85rem !important; transition: all 0.15s ease !important; background-color: #ffffff !important; }
            .custom-pagination nav a:hover { background-color: #e8efeb !important; color: #244e49 !important; border-color: #b7d4ce !important; }
            .custom-pagination nav p { font-size: 0.75rem !important; font-weight: 600 !important; color: #64747a !important; }
            .custom-pagination nav svg { width: 1rem !important; height: 1rem !important; }
        </style>
        {{ $this->ledgers->links() }}
    </div>
</div>
