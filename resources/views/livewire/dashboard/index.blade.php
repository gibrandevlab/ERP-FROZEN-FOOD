<?php

use App\Models\{Product, Ledger};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public int $totalProduk = 0;
    public int $stokMenipis = 0;
    public string $totalPemasukan = '0';
    public string $totalPengeluaran = '0';
    public string $labaKotor = '0';
    public bool $labaPositif = true;
    public string $sapaan = '';
    public string $search = '';

    public function mount(): void
    {
        $jam = now('Asia/Jakarta')->hour;
        $this->sapaan = match (true) {
            $jam < 11 => 'Selamat Pagi',
            $jam < 15 => 'Selamat Siang',
            $jam < 18 => 'Selamat Sore',
            default => 'Selamat Malam',
        };

        $bulanIni = now('Asia/Jakarta')->format('Y-m');

        $this->totalProduk = Product::where('is_active', true)->count();
        $this->stokMenipis = Product::where('is_active', true)
            ->withSum('stocks', 'quantity')
            ->get()
            ->where('stocks_sum_quantity', '<', 10)
            ->count();

        $pemasukan = Ledger::income()
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])
            ->sum('amount');
        $pengeluaran = Ledger::expense()
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])
            ->sum('amount');
        $laba = $pemasukan - $pengeluaran;

        $this->labaPositif = $laba >= 0;
        $this->totalPemasukan = number_format($pemasukan, 0, ',', '.');
        $this->totalPengeluaran = number_format($pengeluaran, 0, ',', '.');
        $this->labaKotor = number_format(abs($laba), 0, ',', '.');
    }
}; ?>

<div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 120)" class="space-y-7">

    {{-- ── Wave Animation Style ────────────────────────────────────────── --}}
    <style>
        @keyframes wave {
            0%, 100% { transform: rotate(0deg); }
            20% { transform: rotate(14deg); }
            40% { transform: rotate(-8deg); }
            60% { transform: rotate(14deg); }
            80% { transform: rotate(-4deg); }
        }
        .animate-wave {
            display: inline-block;
            transform-origin: 70% 70%;
            animation: wave 2.2s infinite;
        }
    </style>

    {{-- ── HEADER & SEARCH BAR (FIXED FLEX CONTAINER) ───────────────────── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 px-5 py-5 sm:px-7 sm:py-6 shadow-sm flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

        {{-- Greeting & Name --}}
        <div>
            <p class="text-[11px] font-extrabold text-blue-600 uppercase tracking-[0.18em]">{{ $sapaan }},</p>
            <h1 class="flex flex-wrap items-center gap-2 text-2xl sm:text-3xl font-extrabold text-slate-950 tracking-tight mt-1">
                <span>{{ auth()->user()->name ?? 'Pengguna' }}</span>
                <span class="animate-wave text-2xl">👋</span>
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-1 max-w-xl">Berikut adalah ringkasan data inventaris Anda hari ini.</p>
        </div>

        {{-- Search Bar & Date Indicator --}}
        <div class="flex items-center gap-3 w-full lg:w-auto">
            {{-- Global Search Bar --}}
            <div class="relative flex-1 lg:w-72">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" wire:model.live="search" placeholder="Cari menu, produk, atau informasi..."
                       class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200 rounded-2xl text-xs font-medium focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm placeholder:text-slate-400">
            </div>

            {{-- Date Card --}}
            <div class="hidden sm:flex items-center gap-3 rounded-2xl bg-white px-4 py-2 border border-slate-200/80 shadow-sm shrink-0">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 002-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="text-right sm:text-left">
                    <p class="text-xs font-bold text-slate-800">{{ now('Asia/Jakarta')->isoFormat('D MMMM Y') }}</p>
                    <p class="text-[10px] font-semibold text-slate-400">Ringkasan bulan ini</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── SKELETON LOADING STATE ─────────────────────────────────────── --}}
    <div x-show="!loaded" class="space-y-6" x-cloak>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            @for ($i = 0; $i < 4; $i++)
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 animate-pulse">
                    <div class="flex items-center justify-between mb-3">
                        <div class="h-3 bg-slate-200 rounded w-20"></div>
                        <div class="w-9 h-9 bg-slate-100 rounded-xl"></div>
                    </div>
                    <div class="h-7 bg-slate-200 rounded-lg w-16 mb-2"></div>
                    <div class="h-4 bg-slate-100 rounded-full w-full mt-3"></div>
                </div>
            @endfor
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @for ($i = 0; $i < 8; $i++)
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 animate-pulse h-28 flex flex-col justify-between">
                    <div class="w-10 h-10 bg-slate-100 rounded-xl"></div>
                    <div class="space-y-1.5">
                        <div class="h-3.5 bg-slate-200 rounded w-24"></div>
                        <div class="h-2.5 bg-slate-100 rounded w-32"></div>
                    </div>
                </div>
            @endfor
        </div>
    </div>

    {{-- ── ACTUAL CONTENT ─────────────────────────────────────────────── --}}
    <div x-show="loaded" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0" class="space-y-7">

        {{-- ── KPI STATISTIC CARDS ──────────────────────────────────────── --}}
        <div class="grid grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">

            {{-- 1. Produk Aktif --}}
            <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
               class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-blue-200 transition-all group flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold text-slate-500">Produk Aktif</span>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-2xl sm:text-3xl font-extrabold text-slate-800 tracking-tight">{{ $totalProduk }}</span>
                            <svg class="w-4 h-4 text-blue-500 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                </div>
                {{-- Sparkline Chart --}}
                <div class="mt-3 pt-2 border-t border-slate-50 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-slate-400">Total terdaftar</span>
                    <svg class="w-16 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 100 30">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M0 25 Q25 10 50 20 T100 5"/>
                    </svg>
                </div>
            </a>

            {{-- 2. Stok Menipis --}}
            <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
               class="bg-white rounded-2xl border p-4 sm:p-5 shadow-sm hover:shadow-md transition-all group flex flex-col justify-between relative overflow-hidden {{ $stokMenipis > 0 ? 'border-amber-300 bg-amber-50/20 hover:border-amber-400' : 'border-slate-200/80 hover:border-amber-200' }}">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold {{ $stokMenipis > 0 ? 'text-amber-700' : 'text-slate-500' }}">Stok Menipis</span>
                        <div class="mt-2 flex items-baseline gap-2">
                            <span class="text-2xl sm:text-3xl font-extrabold tracking-tight {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ $stokMenipis }}</span>
                            <svg class="w-4 h-4 text-amber-500 opacity-0 group-hover:opacity-100 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $stokMenipis > 0 ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-400' }} group-hover:bg-amber-500 group-hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    </div>
                </div>
                {{-- Sparkline Chart --}}
                <div class="mt-3 pt-2 border-t border-slate-50 flex items-center justify-between">
                    <span class="text-[10px] font-semibold {{ $stokMenipis > 0 ? 'text-amber-600' : 'text-slate-400' }}">{{ $stokMenipis > 0 ? 'Perlu Restock' : 'Stok Aman' }}</span>
                    <svg class="w-16 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 100 30">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M0 20 Q30 5 60 25 T100 15"/>
                    </svg>
                </div>
            </a>

            {{-- 3. Pemasukan --}}
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-emerald-200 transition-all group flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold text-slate-500">Pemasukan Bulan Ini</span>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-xl sm:text-2xl font-extrabold text-emerald-600 tracking-tight">Rp {{ $totalPemasukan }}</span>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                    </div>
                </div>
                {{-- Sparkline Chart --}}
                <div class="mt-3 pt-2 border-t border-slate-50 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-emerald-600">Arus Kas Masuk</span>
                    <svg class="w-16 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 100 30">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M0 28 L20 18 L40 22 L70 8 L100 3"/>
                    </svg>
                </div>
            </a>

            {{-- 4. Laba Kotor --}}
            <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate @click="playClick()"
               class="bg-white rounded-2xl border border-slate-200/80 p-4 sm:p-5 shadow-sm hover:shadow-md hover:border-purple-200 transition-all group flex flex-col justify-between relative overflow-hidden">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <span class="text-xs font-bold text-slate-500">Laba Kotor Bulan Ini</span>
                        <div class="mt-2 flex items-baseline gap-1">
                            <span class="text-xl sm:text-2xl font-extrabold tracking-tight {{ $labaPositif ? 'text-purple-700' : 'text-rose-600' }}">
                                {{ $labaPositif ? '' : '-' }}Rp {{ $labaKotor }}
                            </span>
                        </div>
                    </div>
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $labaPositif ? 'bg-purple-50 text-purple-600' : 'bg-rose-50 text-rose-600' }} group-hover:bg-purple-600 group-hover:text-white transition-colors">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                </div>
                {{-- Sparkline Chart --}}
                <div class="mt-3 pt-2 border-t border-slate-50 flex items-center justify-between">
                    <span class="text-[10px] font-semibold text-purple-600">Estimasi Keuntungan</span>
                    <svg class="w-16 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 100 30">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M0 25 C30 25, 40 10, 70 15 T100 2"/>
                    </svg>
                </div>
            </a>

        </div>

        {{-- ── MENU CEPAT / QUICK ACTIONS GRID ──────────────────────────── --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between px-1">
                <div>
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 tracking-tight">Menu Cepat</h2>
                    <p class="text-xs font-medium text-slate-500 mt-0.5">Akses fitur utama dengan cepat.</p>
                </div>
            </div>

            {{-- Grid Responsif 2 Kolom (Mobile) - 3 Kolom (Tablet) - 4 Kolom (Desktop) --}}
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3.5 sm:gap-4">

                {{-- 1. Tambah Produk --}}
                <a href="{{ route('stok.tambah') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-blue-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center text-blue-600 group-hover:bg-blue-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-blue-600 transition-colors">Tambah Produk</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Input ke inventaris</p>
                    </div>
                </a>

                {{-- 2. Catat Transaksi --}}
                <a href="{{ route('pembukuan.tambah') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Catat Transaksi</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Pemasukan / pengeluaran</p>
                    </div>
                </a>

                {{-- 3. Lihat Ringkasan --}}
                <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-emerald-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-600 group-hover:bg-emerald-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-emerald-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition-colors">Lihat Ringkasan</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Laporan bulanan</p>
                    </div>
                </a>

                {{-- 4. Kelola Pengguna --}}
                @if (auth()->user()->is_admin ?? true)
                <a href="{{ route('admin.pengguna.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-purple-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center text-purple-600 group-hover:bg-purple-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-purple-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-purple-600 transition-colors">Kelola Pengguna</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Hak akses tim</p>
                    </div>
                </a>

                {{-- 5. Histori Transaksi --}}
                <a href="{{ route('pembukuan.histori') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-indigo-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center text-indigo-600 group-hover:bg-indigo-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors">Histori Transaksi</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Audit log transaksi</p>
                    </div>
                </a>

                {{-- 6. Mutasi Stok --}}
                <a href="{{ route('stok.histori') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-teal-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-teal-50 rounded-xl flex items-center justify-center text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-teal-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-teal-600 transition-colors">Mutasi Stok</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Pergerakan masuk & keluar</p>
                    </div>
                </a>
                @endif

                {{-- 7. SPK Restock --}}
                <a href="{{ route('spk.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-violet-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center text-violet-600 group-hover:bg-violet-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-violet-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-violet-600 transition-colors">SPK Restock</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Analisis Entropy + SAW</p>
                    </div>
                </a>

                {{-- 8. Pelanggan --}}
                <a href="{{ route('pelanggan.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-orange-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center text-orange-600 group-hover:bg-orange-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-orange-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-orange-600 transition-colors">Pelanggan</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Database & transaksi</p>
                    </div>
                </a>

                {{-- 9. Supplier --}}
                <a href="{{ route('supplier.index') }}" wire:navigate @click="playClick()"
                   class="btn-sound group flex flex-col justify-between p-4 sm:p-5 bg-white rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md hover:border-sky-300 transition-all">
                    <div class="flex items-center justify-between w-full mb-4">
                        <div class="w-10 h-10 bg-sky-50 rounded-xl flex items-center justify-center text-sky-600 group-hover:bg-sky-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <svg class="w-4 h-4 text-slate-300 group-hover:text-sky-500 group-hover:translate-x-0.5 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-slate-800 group-hover:text-sky-600 transition-colors">Data Supplier</p>
                        <p class="text-[11px] text-slate-400 mt-0.5 line-clamp-1">Kelola pemasok barang</p>
                    </div>
                </a>

            </div>
        </div>

    </div>
</div>
