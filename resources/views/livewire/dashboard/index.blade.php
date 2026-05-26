<?php

use App\Models\{Product, Ledger};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public int    $totalProduk      = 0;
    public int    $stokMenipis      = 0;
    public string $totalPemasukan   = '0';
    public string $totalPengeluaran = '0';
    public string $labaKotor        = '0';
    public bool   $labaPositif      = true;
    public string $sapaan           = '';

    public function mount(): void
    {
        $jam = now('Asia/Jakarta')->hour;
        $this->sapaan = match(true) {
            $jam < 11 => 'Selamat Pagi',
            $jam < 15 => 'Selamat Siang',
            $jam < 18 => 'Selamat Sore',
            default   => 'Selamat Malam',
        };

        $bulanIni = now()->format('Y-m');

        $this->totalProduk  = Product::where('is_active', true)->count();
        $this->stokMenipis  = Product::where('is_active', true)
                                     ->withSum('stocks', 'quantity')
                                     ->get()
                                     ->where('stocks_sum_quantity', '<', 10)
                                     ->count();

        $pemasukan   = Ledger::income()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])->sum('amount');
        $pengeluaran = Ledger::expense()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanIni])->sum('amount');
        $laba        = $pemasukan - $pengeluaran;

        $this->labaPositif      = $laba >= 0;
        $this->totalPemasukan   = number_format($pemasukan, 0, ',', '.');
        $this->totalPengeluaran = number_format($pengeluaran, 0, ',', '.');
        $this->labaKotor        = number_format(abs($laba), 0, ',', '.');
    }
}; ?>

<div x-data="{ loaded: false }" x-init="setTimeout(() => loaded = true, 150)" class="space-y-6 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Greeting ──────────────────────────────────────────────────────── --}}
    <style>
        @keyframes wave {
            0% { transform: rotate(0.0deg) }
            10% { transform: rotate(14.0deg) }
            20% { transform: rotate(-8.0deg) }
            30% { transform: rotate(14.0deg) }
            40% { transform: rotate(-4.0deg) }
            50% { transform: rotate(10.0deg) }
            60% { transform: rotate(0.0deg) }
            100% { transform: rotate(0.0deg) }
        }
        .animate-wave {
            display: inline-block;
            transform-origin: 70% 70%;
            animation: wave 2.5s infinite;
        }
    </style>
    <div class="flex items-center justify-between">
        <div>
            <p class="text-xs font-bold text-blue-600 uppercase tracking-widest mb-1">{{ $sapaan }}</p>
            <h1 class="text-xl font-extrabold text-slate-800 flex items-center gap-2">
                <span class="bg-clip-text text-transparent bg-gradient-to-r from-slate-800 to-slate-500">
                    {{ auth()->user()->name }}
                </span>
                <span class="animate-wave text-2xl drop-shadow-sm">👋</span>
            </h1>
        </div>
        <div class="text-right hidden sm:block">
            <p class="text-xs text-slate-400">{{ now('Asia/Jakarta')->isoFormat('dddd, D MMMM Y') }}</p>
            <p class="text-xs font-semibold text-slate-500 mt-0.5">Ringkasan bulan ini</p>
        </div>
    </div>

    {{-- ── Skeleton Loading ──────────────────────────────────────────────── --}}
    <div x-show="!loaded" class="space-y-6" x-cloak>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
            @for($i = 0; $i < 4; $i++)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 animate-pulse">
                <div class="w-10 h-10 bg-slate-100 rounded-xl mb-4"></div>
                <div class="h-7 bg-slate-100 rounded-lg w-16 mb-2"></div>
                <div class="h-3 bg-slate-100 rounded-full w-24"></div>
            </div>
            @endfor
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden animate-pulse">
            @for($i = 0; $i < 3; $i++)
            <div class="flex items-center gap-4 px-5 py-4 border-b border-slate-50">
                <div class="w-10 h-10 bg-slate-100 rounded-xl flex-shrink-0"></div>
                <div class="flex-1 space-y-2">
                    <div class="h-3.5 bg-slate-100 rounded-full w-32"></div>
                    <div class="h-3 bg-slate-100 rounded-full w-48"></div>
                </div>
            </div>
            @endfor
        </div>
    </div>

    {{-- ── Actual Content ─────────────────────────────────────────────────── --}}
    <div x-show="loaded"
         x-transition:enter="transition-opacity duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         class="space-y-6">

        {{-- ── KPI Cards 2×2 ──────────────────────────────────────────────── --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">

            {{-- Produk Aktif --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="w-11 h-11 bg-blue-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <p class="text-2xl font-bold text-blue-600">{{ $totalProduk }}</p>
                {{-- Fix #3: label lebih gelap agar terbaca di bawah terik --}}
                <p class="text-xs text-slate-500 font-medium mt-1">Produk Aktif</p>
            </div>

            {{-- Stok Menipis --}}
            <div class="bg-white rounded-2xl border shadow-sm p-5 {{ $stokMenipis > 0 ? 'border-amber-200' : 'border-slate-100' }}">
                <div class="w-11 h-11 {{ $stokMenipis > 0 ? 'bg-amber-50' : 'bg-slate-50' }} rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 {{ $stokMenipis > 0 ? 'text-amber-500' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                {{-- Fix #2: warna oranye/merah jika stok menipis, semantic color --}}
                <p class="text-2xl font-bold {{ $stokMenipis > 0 ? 'text-orange-500' : 'text-slate-700' }}">{{ $stokMenipis }}</p>
                <p class="text-xs font-medium mt-1 {{ $stokMenipis > 0 ? 'text-orange-400' : 'text-slate-500' }}">
                    {{ $stokMenipis > 0 ? 'Perlu perhatian!' : 'Stok Menipis' }}
                </p>
            </div>

            {{-- Pemasukan --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                    </svg>
                </div>
                <p class="text-lg font-bold text-emerald-600 leading-tight">Rp {{ $totalPemasukan }}</p>
                <p class="text-xs text-slate-500 font-medium mt-1">Pemasukan Bulan Ini</p>
            </div>

            {{-- Laba Kotor --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5">
                <div class="w-11 h-11 {{ $labaPositif ? 'bg-blue-50' : 'bg-red-50' }} rounded-xl flex items-center justify-center mb-4">
                    <svg class="w-6 h-6 {{ $labaPositif ? 'text-blue-600' : 'text-red-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <p class="text-lg font-bold {{ $labaPositif ? 'text-blue-600' : 'text-red-500' }} leading-tight">
                    {{ $labaPositif ? '' : '-' }}Rp {{ $labaKotor }}
                </p>
                <p class="text-xs text-slate-500 font-medium mt-1">Laba Kotor Bulan Ini</p>
            </div>

        </div>

        {{-- ── Aksi Cepat — List Tiles ────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.06), 0 1px 8px rgba(0,0,0,0.04);">
            <div class="px-5 py-3.5 border-b border-slate-50">
                <h2 class="text-xs font-bold text-slate-400 uppercase tracking-widest">Aksi Cepat</h2>
            </div>

            <a href="{{ route('stok.tambah') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-blue-50/40 active:bg-blue-100/50 transition-colors group">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-blue-100 transition-colors">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-blue-600 transition-colors">Tambah Produk</p>
                    <p class="text-xs text-slate-400 mt-0.5">Tambah produk baru ke inventaris</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('pembukuan.tambah') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-blue-50/40 active:bg-blue-100/50 transition-colors group">
                <div class="w-10 h-10 bg-indigo-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-indigo-100 transition-colors">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4v16m8-8H4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-indigo-600 transition-colors">Catat Transaksi</p>
                    <p class="text-xs text-slate-400 mt-0.5">Rekam pemasukan atau pengeluaran</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-indigo-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 {{ auth()->user()->is_admin ? 'border-b border-slate-50' : '' }} hover:bg-blue-50/40 active:bg-blue-100/50 transition-colors group">
                <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-emerald-100 transition-colors">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-emerald-600 transition-colors">Lihat Ringkasan</p>
                    <p class="text-xs text-slate-400 mt-0.5">Laporan keuangan bulanan</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-emerald-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            @if(auth()->user()->is_admin)
            <a href="{{ route('admin.pengguna.index') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-blue-50/40 active:bg-blue-100/50 transition-colors group">
                <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-purple-100 transition-colors">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-purple-600 transition-colors">Kelola Pengguna</p>
                    <p class="text-xs text-slate-400 mt-0.5">Atur hak akses tim Anda</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-purple-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>
            @endif

            <a href="{{ route('spk.index') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-violet-50/40 active:bg-violet-100/50 transition-colors group">
                <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-violet-100 transition-colors">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-violet-600 transition-colors">SPK Prioritas Restock</p>
                    <p class="text-xs text-slate-400 mt-0.5">Analisis Entropy + SAW produk inventaris</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-violet-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('pelanggan.index') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 border-b border-slate-50 hover:bg-blue-50/40 active:bg-blue-100/50 transition-colors group">
                <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-orange-100 transition-colors">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-orange-600 transition-colors">Data Pelanggan</p>
                    <p class="text-xs text-slate-400 mt-0.5">Lihat daftar dan transaksi pelanggan</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-orange-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>

            <a href="{{ route('supplier.index') }}" wire:navigate
               @click="playClick()"
               class="btn-sound flex items-center gap-4 px-5 py-4 hover:bg-sky-50/40 active:bg-sky-100/50 transition-colors group">
                <div class="w-10 h-10 bg-sky-50 rounded-xl flex items-center justify-center flex-shrink-0 group-hover:bg-sky-100 transition-colors">
                    <svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-slate-700 group-hover:text-sky-600 transition-colors">Data Supplier</p>
                    <p class="text-xs text-slate-400 mt-0.5">Kelola pemasok stok barang</p>
                </div>
                <svg class="w-4 h-4 text-slate-300 group-hover:text-sky-400 group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </a>


        </div>

    </div>{{-- end loaded --}}
</div>
