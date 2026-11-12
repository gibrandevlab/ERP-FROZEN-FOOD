<?php
// Tidak ada logika PHP — navigasi sepenuhnya statis dengan helper request()->routeIs()
?>

<ul class="space-y-0.5 px-3">

    {{-- ── Dashboard ─────────────────────────────────────────────── --}}
    <li>
        <a href="{{ route('dashboard') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('dashboard')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🏠</span>
            <span>Dashboard</span>
            @if(request()->routeIs('dashboard'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>

    {{-- ── Asisten AI (DeepSeek Context & Memory) ─────────────────── --}}
    <li>
        <a href="{{ route('chat.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('chat.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">✨</span>
            <span>Asisten AI</span>
            <span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-[#e8efeb] text-[#17343d]">DeepSeek</span>
        </a>
    </li>

    {{-- ── Divider: Bisnis ──────────────────────────────────────── --}}
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-[#71817f]  uppercase tracking-widest">Bisnis</span>
    </li>

    {{-- Stok Produk --}}
    @can('view-stok')
    <li>
        <a href="{{ route('stok.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('stok.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">📦</span>
            <span>Stok Produk</span>
            @if(request()->routeIs('stok.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Kategori --}}
    @can('view-kategori')
    <li>
        <a href="{{ route('kategori.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('kategori.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🏷️</span>
            <span>Kategori</span>
            @if(request()->routeIs('kategori.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Lokasi Stok --}}
    @can('view-lokasi')
    <li>
        <a href="{{ route('lokasi.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('lokasi.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">📍</span>
            <span>Lokasi Stok</span>
            @if(request()->routeIs('lokasi.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Pelanggan --}}
    @can('view-pelanggan')
    <li>
        <a href="{{ route('pelanggan.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('pelanggan.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🤝</span>
            <span>Pelanggan</span>
            @if(request()->routeIs('pelanggan.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Supplier --}}
    @can('view-supplier')
    <li>
        <a href="{{ route('supplier.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('supplier.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🏢</span>
            <span>Supplier</span>
            @if(request()->routeIs('supplier.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Purchase Request --}}
    @can('view-purchase_request')
    <li>
        <a href="{{ route('purchase-request.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('purchase-request.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🧾</span>
            <span>Permintaan Pembelian</span>
            @if(request()->routeIs('purchase-request.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Persetujuan Pembelian --}}
    @can('view-purchase_approval')
    <li>
        <a href="{{ route('purchase-approval.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('purchase-approval.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">✅</span>
            <span>Persetujuan PR</span>
            @if(request()->routeIs('purchase-approval.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- SPK --}}
    @can('view-spk')
    <li>
        <a href="{{ route('spk.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('spk.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">🧠</span>
            <span>SPK Restock</span>
            @if(request()->routeIs('spk.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- Forecast Restock --}}
    @can('view-forecast')
    <li>
        <a href="{{ route('forecast.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('forecast.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">📈</span>
            <span>Forecast Restock</span>
            @if(request()->routeIs('forecast.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    {{-- ── Divider: Keuangan ────────────────────────────────────── --}}
    @can('view-pembukuan')
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-[#71817f]  uppercase tracking-widest">Keuangan</span>
    </li>

    <li>
        <a href="{{ route('pembukuan.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('pembukuan.index') || request()->routeIs('pembukuan.tambah') || request()->routeIs('pembukuan.edit')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">📒</span>
            <span>Transaksi</span>
            @if(request()->routeIs('pembukuan.index') || request()->routeIs('pembukuan.tambah') || request()->routeIs('pembukuan.edit'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    @can('view-ringkasan')
    <li>
        <a href="{{ route('pembukuan.ringkasan') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 pl-10 rounded-lg text-sm transition-all duration-150
                  {{ request()->routeIs('pembukuan.ringkasan')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#64747a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">📊</span>
            <span>Ringkasan</span>
        </a>
    </li>
    @endcan

    {{-- ── Divider: Admin ───────────────────────────────────────── --}}
    @if(auth()->user()->canDo('pengguna', 'view') || auth()->user()->canDo('audit_logs', 'view'))
    <li class="pt-5 pb-1.5 px-1">
        <span class="text-[10px] font-bold text-[#71817f]  uppercase tracking-widest">Admin</span>
    </li>

    @can('view-pengguna')
    <li>
        <a href="{{ route('admin.pengguna.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('admin.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3]   text-[#315b50]  shadow-none border border-[#dce1de]/60 '
                      : 'text-[#52646a]  hover:bg-[#f5f6f3]  hover:text-[#344950] ' }}">
            <span class="text-base leading-none w-5 text-center">👥</span>
            <span>Pengguna</span>
            @if(request()->routeIs('admin.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan

    @can('view-audit-logs')
    <li>
        <a href="{{ route('admin.audit-log.index') }}" wire:navigate
           @click="playClick(); sidebarOpen = false"
           class="btn-sound flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all duration-150
                  {{ request()->routeIs('admin.audit-log.*')
                      ? 'bg-gradient-to-r from-[#f5f6f3] to-[#f5f6f3] text-[#315b50] shadow-none border border-[#dce1de]/60'
                      : 'text-[#52646a] hover:bg-[#f5f6f3] hover:text-[#344950]' }}">
            <span class="text-base leading-none w-5 text-center">🧾</span>
            <span>Riwayat Aktivitas</span>
            @if(request()->routeIs('admin.audit-log.*'))
                <span class="ml-auto w-1.5 h-1.5 rounded-full bg-[#315b50]"></span>
            @endif
        </a>
    </li>
    @endcan
    @endif

</ul>
