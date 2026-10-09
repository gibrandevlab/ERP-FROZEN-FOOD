<?php

/**
 * Komponen Livewire Volt: Daftar Produk
 * File: resources/views/livewire/stok/daftar-produk.blade.php
 */

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{mount, state, computed};

// ─── State ──────────────────────────────────────────────────────────────────

state([
    'search'      => '',      // Kata pencarian
    'bolehEdit'   => false,   // Apakah user boleh edit produk
    'bolehHapus'  => false,   // Apakah user boleh hapus produk
    'bolehTambah' => false,   // Apakah user boleh tambah produk
]);

// ─── Mount ──────────────────────────────────────────────────────────────────

mount(function () {
    $this->authorize('view-stok');

    $this->bolehTambah = Gate::allows('create-stok');
    $this->bolehEdit   = Gate::allows('edit-stok');
    $this->bolehHapus  = Gate::allows('delete-stok');
});

// ─── Computed ───────────────────────────────────────────────────────────────

$products = computed(function () {
    return Product::with('category')
        ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
        ->where('is_active', true)
        ->latest()
        ->get();
});

// ─── Actions ────────────────────────────────────────────────────────────────

$hapusProduk = function (int $id): void {
    $this->authorize('delete-stok');

    $product = Product::findOrFail($id);
    $nama = $product->name;
    $product->delete();

    session()->flash('success', "Produk '{$nama}' berhasil dihapus.");
};

?>

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Flash Messages ── --}}
    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             class="rounded-2xl border border-emerald-200 bg-emerald-50 p-3.5 text-xs sm:text-sm text-emerald-800 shadow-sm flex items-center justify-between gap-3">
            <span class="font-bold flex items-center gap-2">
                <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </span>
            <button type="button" @click="show = false" class="text-emerald-600 hover:text-emerald-800 font-bold p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    @endif

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Katalog Inventaris</span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Daftar Produk
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Kelola data seluruh produk aktif dalam sistem inventaris.</p>
        </div>

        @if ($bolehTambah)
            <a href="{{ route('stok.tambah') }}" wire:navigate @click="playClick()"
               class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-xs sm:text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all shrink-0 self-start sm:self-auto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Tambah Produk</span>
            </a>
        @endif
    </div>

    {{-- ── Search Bar ── --}}
    <div class="relative flex-1">
        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        </div>
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama produk..."
               class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse ($this->products as $product)
            <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-extrabold text-slate-900 text-sm truncate">{{ $product->name }}</p>
                        <p class="text-xs text-slate-500 font-medium mt-0.5">
                            Stok: <span class="font-bold text-slate-800">{{ $product->totalStock() }} {{ $product->unit }}</span>
                        </p>
                        <p class="text-xs font-extrabold text-blue-600 mt-0.5">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    @if ($bolehEdit)
                        <a href="{{ route('stok.edit', $product->slug) }}" wire:navigate @click="playClick()"
                           class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 hover:bg-blue-100 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit</span>
                        </a>
                    @endif

                    @if ($bolehHapus)
                        <button wire:click="hapusProduk({{ $product->id }})"
                                wire:confirm="Yakin ingin menghapus produk '{{ $product->name }}'?"
                                @click="playDanger()"
                                class="btn-sound inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100 hover:bg-rose-100 transition-all">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            <span>Hapus</span>
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center shadow-sm">
                <p class="text-slate-400 text-xs sm:text-sm font-semibold">Tidak ada produk yang ditemukan.</p>
            </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Nama Produk</th>
                    <th class="px-6 py-4 text-center">Total Stok</th>
                    <th class="px-6 py-4 text-right">Harga Jual</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse ($this->products as $product)
                    <tr class="hover:bg-blue-50/30 transition-colors">
                        <td class="px-6 py-4 font-extrabold text-slate-900">
                            {{ $product->name }}
                        </td>
                        <td class="px-6 py-4 text-center font-bold text-slate-700">
                            {{ $product->totalStock() }} <span class="text-xs text-slate-400 font-semibold">{{ $product->unit }}</span>
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-slate-900">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                @if ($bolehEdit)
                                    <a href="{{ route('stok.edit', $product->slug) }}" wire:navigate @click="playClick()"
                                       class="btn-sound inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 text-blue-700 text-xs font-bold border border-blue-100 hover:bg-blue-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        <span>Edit</span>
                                    </a>
                                @endif

                                @if ($bolehHapus)
                                    <button wire:click="hapusProduk({{ $product->id }})"
                                            wire:confirm="Yakin ingin menghapus produk '{{ $product->name }}'?"
                                            @click="playDanger()"
                                            class="btn-sound inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold border border-rose-100 hover:bg-rose-100 transition-all">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        <span>Hapus</span>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-xs sm:text-sm font-semibold">
                            Tidak ada produk yang ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

</div>
