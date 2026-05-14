<?php

/**
 * ─────────────────────────────────────────────────────────────────────────────
 * CONTOH — Komponen Livewire Volt: Daftar Produk
 * File: resources/views/livewire/stok/daftar-produk.blade.php
 *
 * Menunjukkan cara menggunakan sistem otorisasi di komponen Volt:
 *  - $this->authorize() → lempar 403 jika tidak punya akses
 *  - Gate::allows()     → cek akses dan return true/false
 *  - @can di Blade      → tampilkan/sembunyikan elemen UI
 * ─────────────────────────────────────────────────────────────────────────────
 */

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use function Livewire\Volt\{mount, state, computed};

// ─── State ──────────────────────────────────────────────────────────────────

state([
    'search'   => '',      // Kata pencarian
    'bolehEdit'   => false, // Apakah user boleh edit produk
    'bolehHapus'  => false, // Apakah user boleh hapus produk
    'bolehTambah' => false, // Apakah user boleh tambah produk
]);

// ─── Mount ──────────────────────────────────────────────────────────────────

mount(function () {
    // 🔒 Lempar HTTP 403 jika user tidak punya akses lihat produk
    $this->authorize('view-products');

    // Simpan cek akses ke state agar tidak query berulang di Blade
    $this->bolehTambah = Gate::allows('create-products');
    $this->bolehEdit   = Gate::allows('edit-products');
    $this->bolehHapus  = Gate::allows('delete-products');
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
    // 🔒 Double-check di server, jangan andalkan UI saja
    $this->authorize('delete-products');

    Product::findOrFail($id)->delete();
};

?>

{{-- ─── Template Blade ─────────────────────────────────────────────────── --}}

<div class="p-6">

    {{-- Header halaman --}}
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold">Daftar Produk</h1>

        {{-- Tombol tambah hanya muncul jika user punya akses create --}}
        @if ($bolehTambah)
            <a href="{{ route('produk.tambah') }}"
               class="px-4 py-2 bg-black text-white rounded-lg hover:bg-gray-800 transition">
                + Tambah Produk
            </a>
        @endif
    </div>

    {{-- Search bar --}}
    <input wire:model.live="search"
           type="text"
           placeholder="Cari produk..."
           class="w-full px-4 py-2 border rounded-lg mb-6" />

    {{-- Daftar produk --}}
    <div class="divide-y">
        @forelse ($this->products as $product)
            <div class="py-4 flex items-center justify-between">

                {{-- Info produk --}}
                <div>
                    <p class="font-medium">{{ $product->name }}</p>
                    <p class="text-sm text-gray-500">
                        Stok: {{ $product->totalStock() }} {{ $product->unit }}
                        · Rp {{ number_format($product->price, 0, ',', '.') }}
                    </p>
                </div>

                {{-- Tombol aksi — hanya muncul sesuai hak akses --}}
                <div class="flex gap-2">
                    @if ($bolehEdit)
                        <a href="{{ route('produk.edit', $product->slug) }}"
                           class="px-3 py-1 text-sm border rounded hover:bg-gray-100 transition">
                            Edit
                        </a>
                    @endif

                    @if ($bolehHapus)
                        <button wire:click="hapusProduk({{ $product->id }})"
                                wire:confirm="Yakin ingin menghapus produk '{{ $product->name }}'?"
                                class="px-3 py-1 text-sm border border-red-300 text-red-600 rounded hover:bg-red-50 transition">
                            Hapus
                        </button>
                    @endif
                </div>
            </div>
        @empty
            <p class="py-6 text-center text-gray-400">Tidak ada produk yang ditemukan.</p>
        @endforelse
    </div>

</div>
