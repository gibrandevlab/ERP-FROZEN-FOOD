<?php

use App\Models\{Ledger, User};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterType  = '';
    public string $filterBulan = '';
    public string $filterUser  = '';

    public function mount(): void
    {
        $this->authorize('view-pembukuan');
    }

    public function getLedgersProperty()
    {
        return Ledger::with(['product', 'location', 'customer', 'supplier', 'user', 'updater'])
            ->when($this->search,      fn($q) => $q->where('title', 'like', "%{$this->search}%")
                                                    ->orWhere('reference', 'like', "%{$this->search}%"))
            ->when($this->filterType,  fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterBulan, fn($q) => $q->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$this->filterBulan]))
            ->when($this->filterUser,  fn($q) => $q->where('user_id', $this->filterUser))
            ->orderByDesc('date')->orderByDesc('id')
            ->paginate(20);
    }

    public function getUsersProperty()
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function updatedSearch():      void { $this->resetPage(); }
    public function updatedFilterType():  void { $this->resetPage(); }
    public function updatedFilterBulan(): void { $this->resetPage(); }
    public function updatedFilterUser():  void { $this->resetPage(); }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <a href="{{ route('pembukuan.index') }}" wire:navigate
                   class="text-slate-400 hover:text-blue-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-xs font-semibold text-blue-600">Histori</span>
            </div>
            <h1 class="text-xl font-extrabold" style="color:#1E293B;">Histori Pembukuan Lengkap</h1>
            <p class="text-xs text-slate-500 mt-0.5">Semua catatan transaksi beserta info penginput</p>
        </div>
        <a href="{{ route('pembukuan.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white shadow-lg shadow-blue-200/50 hover:opacity-90 transition-all"
           style="background:linear-gradient(135deg,#2563EB,#4F46E5);">
            <span>+</span><span class="hidden sm:inline">Catat Transaksi</span>
        </a>
    </div>

    {{-- ── Filter Bar ──────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari judul / referensi..."
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all" />
        <select wire:model.live="filterType"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 shadow-sm">
            <option value="">Semua Tipe</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
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
        @forelse($this->ledgers as $l)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm relative overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $l->type === 'income' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
            <div class="pl-2 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800 text-sm truncate">{{ $l->title }}</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                    </div>
                    <p class="text-sm font-extrabold flex-shrink-0 {{ $l->type === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount,0,',','.') }}
                    </p>
                </div>
                <div class="flex flex-wrap gap-1.5 pt-2 border-t border-slate-50 text-[10px] font-semibold">
                    <span class="px-2 py-0.5 rounded-full {{ $l->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                        {{ $l->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </span>
                    @if($l->user)
                    <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                        📝 {{ $l->user->name }}
                    </span>
                    @endif
                    @if($l->updater && $l->updater->id !== optional($l->user)->id)
                    <span class="px-2 py-0.5 rounded-full bg-violet-50 text-violet-600">
                        ✏️ {{ $l->updater->name }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm font-semibold">Belum ada catatan transaksi.</p>
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
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Keterangan</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Mutasi Stok</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Keuangan</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Nominal</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Diinput Oleh</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Diedit Oleh</th>
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
                        @if($l->customer)<p class="text-xs text-orange-500 mt-0.5">👤 {{ $l->customer->name }}</p>@endif
                        @if($l->supplier)<p class="text-xs text-sky-500 mt-0.5">🏭 {{ $l->supplier->name }}</p>@endif
                    </td>
                    <td class="px-5 py-4">
                        @if($l->stock_movement)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600' : 'bg-orange-50 text-orange-600' }}">
                                    {{ $l->stock_movement === 'in' ? '+ Masuk' : '- Keluar' }}
                                </span>
                                <span class="text-xs font-bold text-slate-700">{{ $l->quantity }} pcs</span>
                            </div>
                            @if($l->location)<p class="text-xs text-slate-400 mt-1">📍 {{ $l->location->name }}</p>@endif
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $l->type==='income' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                            {{ $l->type==='income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right font-bold {{ $l->type==='income' ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $l->type==='income' ? '+' : '-' }} Rp {{ number_format($l->amount,0,',','.') }}
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
                        @if($l->updater && $l->updater_id !== $l->user_id)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full bg-violet-100 flex items-center justify-center text-violet-700 text-xs font-bold flex-shrink-0">
                                {{ strtoupper(substr($l->updater->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">{{ $l->updater->name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $l->updated_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @elseif($l->updater)
                        <span class="text-[10px] text-slate-400 italic">same as creator</span>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-5 py-4 text-right">
                        <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate
                           class="w-8 h-8 rounded-lg bg-slate-50 inline-flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-5 py-12 text-center text-slate-400 text-sm">Belum ada catatan transaksi untuk filter ini.</td></tr>
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
