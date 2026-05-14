<?php
// Tidak ada logika PHP — navigasi sepenuhnya statis dengan helper request()->routeIs()
?>

<ul class="space-y-0.5 px-3">

    {{-- ── Dashboard ─────────────────────────────────────────────── --}}
    <li>
        <a href="{{ route('dashboard') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('dashboard')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">🏠</span>
            <span>Dashboard</span>
            @if(request()->routeIs('dashboard'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>

    {{-- ── Divider: Bisnis ──────────────────────────────────────── --}}
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest">Bisnis</span>
    </li>

    {{-- Stok Produk --}}
    @can('view-products')
    <li>
        <a href="{{ route('stok.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('stok.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">📦</span>
            <span>Stok Produk</span>
            @if(request()->routeIs('stok.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Kategori --}}
    @can('view-categories')
    <li>
        <a href="{{ route('kategori.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('kategori.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">🏷️</span>
            <span>Kategori</span>
            @if(request()->routeIs('kategori.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Pelanggan --}}
    <li>
        <a href="{{ route('pelanggan.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('pelanggan.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">🤝</span>
            <span>Pelanggan</span>
            @if(request()->routeIs('pelanggan.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>

    {{-- Supplier --}}
    <li>
        <a href="{{ route('supplier.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('supplier.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">🏢</span>
            <span>Supplier</span>
            @if(request()->routeIs('supplier.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>

    {{-- SPK --}}
    <li>
        <a href="{{ route('spk.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('spk.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">🧠</span>
            <span>SPK Restock</span>
            @if(request()->routeIs('spk.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>

    {{-- ── Divider: Keuangan ────────────────────────────────────── --}}
    @can('view-ledger')
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest">Keuangan</span>
    </li>

    <li>
        <a href="{{ route('pembukuan.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('pembukuan.index') || request()->routeIs('pembukuan.tambah') || request()->routeIs('pembukuan.edit')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">📒</span>
            <span>Transaksi</span>
            @if(request()->routeIs('pembukuan.index') || request()->routeIs('pembukuan.tambah') || request()->routeIs('pembukuan.edit'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>

    <li>
        <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 pl-10 rounded-xl text-sm transition-all duration-150
                  {{ request()->routeIs('pembukuan.ringkasan')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-500 dark:text-slate-500 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-700 dark:hover:text-slate-300' }}">
            <span class="text-base leading-none w-5 text-center">📊</span>
            <span>Ringkasan</span>
        </a>
    </li>
    @endcan

    {{-- ── Divider: Admin ───────────────────────────────────────── --}}
    @if(auth()->user()->is_admin)
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-slate-400 dark:text-slate-600 uppercase tracking-widest">Admin</span>
    </li>

    <li>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('admin.*')
                      ? 'bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-blue-900/20 dark:to-indigo-900/15 text-blue-600 dark:text-blue-400 shadow-sm border border-blue-100/60 dark:border-blue-800/40'
                      : 'text-slate-600 dark:text-slate-400 hover:bg-slate-50 dark:hover:bg-white/5 hover:text-slate-800 dark:hover:text-white' }}">
            <span class="text-base leading-none w-5 text-center">👥</span>
            <span>Pengguna</span>
            @if(request()->routeIs('admin.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-blue-500"></span>
            @endif
        </a>
    </li>
    @endif

</ul>
