<?php

use App\Models\{Ledger, Product};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterType  = '';
    public string $filterBulan = '';

    public function mount(): void
    {
        $this->authorize('view-ledger');
        $this->filterBulan = now()->format('Y-m');
    }

    public function getLedgersProperty()
    {
        return Ledger::with(['product', 'location'])
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%")->orWhere('reference', 'like', "%{$this->search}%"))
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
        $this->authorize('delete-ledger');
        Ledger::findOrFail($id)->delete();
        session()->flash('success', 'Transaksi berhasil dihapus.');
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterType(): void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold" style="color:#1E293B;">Pembukuan</h1>
            <p class="text-xs text-slate-500 mt-0.5">Catatan transaksi keuangan</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate @click="playClick()"
               class="btn-sound flex items-center gap-1.5 px-3 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 shadow-sm transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <span class="hidden sm:inline">Ringkasan</span>
            </a>
            @can('create-ledger')
            <a href="{{ route('pembukuan.tambah') }}" wire:navigate @click="playClick()"
               class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-blue-200/50 hover:opacity-90 transition-all"
               style="background:linear-gradient(135deg,#2563EB,#4F46E5);">
                <span>+</span><span class="hidden sm:inline">Catat Transaksi</span>
            </a>
            @endcan
        </div>
    </div>

    {{-- Mini KPI --}}
    <div class="grid grid-cols-2 gap-3">
        <div class="bg-white rounded-2xl border border-slate-100 px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                </div>
                <div><p class="text-xs text-slate-500 font-medium">Pemasukan</p><p class="text-base font-bold text-emerald-600">Rp {{ $this->totalPemasukan }}</p></div>
            </div>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 px-5 py-4 shadow-sm">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                </div>
                <div><p class="text-xs text-slate-500 font-medium">Pengeluaran</p><p class="text-base font-bold text-red-500">Rp {{ $this->totalPengeluaran }}</p></div>
            </div>
        </div>
    </div>

    {{-- Filter --}}
    <div class="flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari judul / referensi..."
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
        <select wire:model.live="filterType" class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
            <option value="">Semua Tipe</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
        </select>
        <input wire:model.live="filterBulan" type="month"
               class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm" />
    </div>

    {{-- Mobile: Card List --}}
    <div class="space-y-3 sm:hidden pb-4">
        @forelse($this->ledgers as $l)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm relative overflow-hidden">
            {{-- Accent border on left --}}
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $l->type === 'income' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
            
            <div class="flex items-start justify-between gap-3 mb-3 pl-2">
                <div class="min-w-0 flex-1">
                    <p class="font-bold text-slate-800 text-sm truncate">{{ $l->title }}</p>
                    <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                    @if($l->product)
                        <p class="text-[10px] font-bold text-blue-600 mt-1.5 inline-flex items-center gap-1 bg-blue-50 px-2 py-0.5 rounded-md">
                            📦 {{ $l->product->name }}
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <p class="text-sm font-extrabold {{ $l->type === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                    </p>
                    @if($l->stock_movement && $l->quantity)
                        <p class="text-[10px] font-bold mt-1.5 {{ $l->stock_movement === 'in' ? 'text-blue-500 bg-blue-50' : 'text-orange-500 bg-orange-50' }} px-2 py-0.5 rounded-md inline-block">
                            {{ $l->stock_movement === 'in' ? 'Masuk' : 'Keluar' }} {{ $l->quantity }} pcs
                        </p>
                    @endif
                </div>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-50 pl-2">
                @if($l->reference)
                    <p class="text-[10px] text-slate-400 font-mono font-bold bg-slate-50 px-2 py-1 rounded-md">Ref: {{ $l->reference }}</p>
                @else
                    <div></div>
                @endif
                
                <div class="flex items-center gap-2">
                    @can('edit-ledger')
                    <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound px-3 py-1.5 rounded-lg bg-blue-50 text-blue-600 text-xs font-bold hover:bg-blue-100 transition-colors">Edit</a>
                    @endcan
                    @can('delete-ledger')
                    <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus catatan '{{ $l->title }}'?"
                            @click="playDanger()"
                            class="btn-sound px-3 py-1.5 rounded-lg bg-red-50 text-red-500 text-xs font-bold hover:bg-red-100 transition-colors">Hapus</button>
                    @endcan
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm font-semibold">Belum ada catatan transaksi.</p>
        </div>
        @endforelse
    </div>

    {{-- Desktop: Table --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-x-auto" style="box-shadow:0 4px 40px rgba(0,0,0,0.05),0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full min-w-[700px] text-sm">
            <thead style="background:linear-gradient(135deg,rgba(248,250,252,0.95),rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Tanggal</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Mutasi Stok</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Keuangan</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->ledgers as $l)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-5 py-4 text-slate-400 whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-5 py-4">
                        <p class="font-semibold text-slate-800">{{ $l->title }}</p>
                        @if($l->reference)<p class="text-xs text-slate-400 mt-0.5">Ref: {{ $l->reference }}</p>@endif
                        @if($l->product)<p class="text-xs font-medium text-blue-600 mt-0.5">📦 {{ $l->product->name }}</p>@endif
                    </td>
                    <td class="px-5 py-4 hidden sm:table-cell">
                        @if($l->stock_movement)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                                    {{ $l->stock_movement === 'in' ? '+ Masuk' : '- Keluar' }}
                                </span>
                                <span class="text-xs font-bold text-slate-700">{{ $l->quantity }} pcs</span>
                            </div>
                            @if($l->location)
                                <p class="text-xs text-slate-400 mt-1">📍 {{ $l->location->name }}</p>
                            @endif
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center hidden md:table-cell">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $l->type==='income' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                            {{ $l->type==='income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right font-bold {{ $l->type==='income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $l->type==='income' ? '+' : '-' }} Rp {{ number_format($l->amount,0,',','.') }}
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            @can('edit-ledger')
                            <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-2.5 py-1 rounded-lg bg-blue-50 text-blue-600 text-xs font-medium border border-blue-100/60 hover:bg-blue-100 transition-colors">Edit</a>
                            @endcan
                            @can('delete-ledger')
                            <button wire:click="hapus({{ $l->id }})" wire:confirm="Hapus catatan '{{ $l->title }}'?"
                                    @click="playDanger()"
                                    class="btn-sound px-2.5 py-1 rounded-lg bg-red-50 text-red-500 text-xs font-medium border border-red-100/60 hover:bg-red-100 transition-colors">Hapus</button>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400 text-sm">Belum ada catatan transaksi untuk periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sticky bottom-4 sm:static mt-6 sm:mt-2 z-40">
        <div class="bg-white/95 sm:bg-transparent backdrop-blur-md sm:backdrop-blur-none border sm:border-0 border-slate-200/60 rounded-2xl sm:rounded-none p-2 sm:p-0 shadow-2xl sm:shadow-none max-w-sm mx-auto sm:max-w-none transition-all">
            {{ $this->ledgers->links() }}
        </div>
    </div>
</div>
