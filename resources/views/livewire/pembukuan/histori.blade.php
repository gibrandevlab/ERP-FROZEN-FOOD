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

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Audit Log Kas</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Histori Pembukuan Lengkap
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Semua catatan transaksi keuangan beserta info entri dan editor.</p>
            </div>
        </div>

        <a href="{{ route('pembukuan.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-xs sm:text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all shrink-0 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Catat Transaksi</span>
        </a>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari judul / referensi..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
        </div>

        <select wire:model.live="filterType"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Tipe</option>
            <option value="income">Pemasukan</option>
            <option value="expense">Pengeluaran</option>
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
        @forelse($this->ledgers as $l)
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm relative overflow-hidden space-y-3">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $l->type === 'income' ? 'bg-emerald-500' : 'bg-rose-500' }}"></div>

            <div class="pl-2 space-y-2">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="font-extrabold text-slate-900 text-sm truncate">{{ $l->title }}</p>
                        <p class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                    </div>
                    <p class="text-sm font-extrabold shrink-0 {{ $l->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-1.5 pt-2 border-t border-slate-100 text-[10px] font-extrabold">
                    <span class="px-2.5 py-1 rounded-lg {{ $l->type === 'income' ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                        {{ $l->type === 'income' ? 'Pemasukan' : 'Pengeluaran' }}
                    </span>

                    @if($l->user)
                    <span class="px-2.5 py-1 rounded-lg bg-blue-50 text-blue-700 border border-blue-100 flex items-center gap-1">
                        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        {{ $l->user->name }}
                    </span>
                    @endif

                    @if($l->updater && $l->updater->id !== optional($l->user)->id)
                    <span class="px-2.5 py-1 rounded-lg bg-purple-50 text-purple-700 border border-purple-100 flex items-center gap-1">
                        <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        {{ $l->updater->name }}
                    </span>
                    @endif
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-xs sm:text-sm font-semibold">Belum ada catatan transaksi.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Tanggal</th>
                    <th class="px-6 py-4">Keterangan</th>
                    <th class="px-6 py-4">Mutasi Stok</th>
                    <th class="px-6 py-4 text-center">Keuangan</th>
                    <th class="px-6 py-4 text-right">Nominal</th>
                    <th class="px-6 py-4">Diinput Oleh</th>
                    <th class="px-6 py-4">Diedit Oleh</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse($this->ledgers as $l)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4 text-slate-400 font-semibold whitespace-nowrap text-xs">{{ $l->date->format('d M Y') }}</td>
                    <td class="px-6 py-4">
                        <p class="font-extrabold text-slate-900">{{ $l->title }}</p>
                        @if($l->reference)<p class="text-xs text-slate-400 font-mono mt-0.5">Ref: {{ $l->reference }}</p>@endif
                        @if($l->product)<p class="text-xs font-bold text-blue-600 mt-0.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg> {{ $l->product->name }}</p>@endif
                        @if($l->customer)<p class="text-xs font-semibold text-amber-600 mt-0.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> {{ $l->customer->name }}</p>@endif
                        @if($l->supplier)<p class="text-xs font-semibold text-sky-600 mt-0.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg> {{ $l->supplier->name }}</p>@endif
                    </td>
                    <td class="px-6 py-4">
                        @if($l->stock_movement)
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider {{ $l->stock_movement === 'in' ? 'bg-blue-50 text-blue-600 border border-blue-100' : 'bg-amber-50 text-amber-600 border border-amber-100' }}">
                                    {{ $l->stock_movement === 'in' ? '+ Masuk' : '- Keluar' }}
                                </span>
                                <span class="text-xs font-extrabold text-slate-800">{{ $l->quantity }} pcs</span>
                            </div>
                            @if($l->location)<p class="text-xs text-slate-400 font-medium mt-1 flex items-center gap-1"><svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg> {{ $l->location->name }}</p>@endif
                        @else
                            <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $l->type==='income' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100' }}">
                            {{ $l->type==='income' ? 'Pemasukan' : 'Pengeluaran' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $l->type==='income' ? 'text-emerald-600' : 'text-rose-600' }}">
                        {{ $l->type==='income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
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
                        @if($l->updater && $l->updater_id !== $l->user_id)
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-xl bg-purple-50 text-purple-600 font-extrabold text-xs flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($l->updater->name, 0, 1)) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ $l->updater->name }}</p>
                                <p class="text-[10px] text-slate-400 font-medium">{{ $l->updated_at->format('d M Y, H:i') }}</p>
                            </div>
                        </div>
                        @elseif($l->updater)
                        <span class="text-[10px] text-slate-400 italic font-medium">Sama seperti penginput</span>
                        @else
                        <span class="text-xs text-slate-400">—</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        <a href="{{ route('pembukuan.edit', $l->slug) }}" wire:navigate @click="playClick()"
                           class="btn-sound p-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-all inline-flex items-center justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-slate-400 text-xs sm:text-sm font-semibold">Belum ada catatan transaksi untuk filter ini.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Container Pagination Seragam ── --}}
    <div class="pt-2 custom-pagination">
        <style>
            .custom-pagination nav[role="navigation"] { display: flex; align-items: center; justify-between; flex-wrap: wrap; gap: 0.75rem; }
            .custom-pagination nav span[aria-current="page"] > span { background-color: #2563eb !important; color: #ffffff !important; border-color: #2563eb !important; border-radius: 0.875rem !important; font-weight: 800 !important; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2) !important; }
            .custom-pagination nav a, .custom-pagination nav span[aria-disabled="true"] > span { border-radius: 0.875rem !important; font-size: 0.75rem !important; font-weight: 700 !important; border-color: #e2e8f0 !important; color: #475569 !important; padding: 0.5rem 0.85rem !important; transition: all 0.15s ease !important; background-color: #ffffff !important; }
            .custom-pagination nav a:hover { background-color: #eff6ff !important; color: #2563eb !important; border-color: #bfdbfe !important; }
            .custom-pagination nav p { font-size: 0.75rem !important; font-weight: 600 !important; color: #64748b !important; }
            .custom-pagination nav svg { width: 1rem !important; height: 1rem !important; }
        </style>
        {{ $this->ledgers->links() }}
    </div>
</div>
