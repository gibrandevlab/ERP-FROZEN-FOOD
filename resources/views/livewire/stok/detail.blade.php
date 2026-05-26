<?php

use App\Models\{Product, Ledger};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {

    public Product $product;

    public function mount(string $slug): void
    {
        $this->authorize('view-products');
        $this->product = Product::with(['category', 'ledgers' => fn($q) => $q->latest()->limit(10)])->where('slug', $slug)->firstOrFail();
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-2">
        <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
           class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-extrabold text-slate-800">{{ $product->name }}</h1>
            <p class="text-sm text-slate-500 mt-0.5">Detail produk dan riwayat transaksi</p>
        </div>
        <a href="{{ route('stok.edit', $product->slug) }}" wire:navigate @click="playClick()"
           class="btn-sound hidden sm:flex items-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 transition-all shadow-sm">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            Edit Produk
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column: Image & Stock --}}
        <div class="space-y-6">
            {{-- Image Card --}}
            <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm" style="box-shadow: 0 4px 40px rgba(0,0,0,0.04);">
                @if($product->image)
                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                         class="w-full aspect-square object-cover" />
                @else
                    <div class="w-full aspect-square bg-slate-50 flex flex-col items-center justify-center text-slate-400">
                        <svg class="w-16 h-16 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-sm font-medium">Tidak ada foto</span>
                    </div>
                @endif
            </div>

            {{-- Stock Card --}}
            <div class="bg-white rounded-3xl border border-slate-100 p-6 shadow-sm text-center {{ $product->totalStock() < 10 ? 'ring-2 ring-amber-400 border-transparent' : '' }}" style="box-shadow: 0 4px 40px rgba(0,0,0,0.04);">
                <p class="text-sm font-bold text-slate-400 uppercase tracking-widest mb-1">Total Stok</p>
                <div class="flex items-end justify-center gap-2">
                    <p class="text-6xl font-extrabold tracking-tight {{ $product->totalStock() < 10 ? 'text-amber-500' : 'text-slate-800' }}">
                        {{ $product->totalStock() }}
                    </p>
                    <p class="text-lg font-bold text-slate-400 mb-1.5">{{ $product->unit }}</p>
                </div>
                @if($product->totalStock() < 10)
                    <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 text-amber-600 rounded-full text-xs font-bold">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Stok menipis, segera restock!
                    </div>
                @endif
                
                <a href="{{ route('pembukuan.tambah', ['produk' => $product->id]) }}" wire:navigate @click="playClick()"
                   class="btn-sound inline-flex items-center gap-2 px-3 py-1.5 mt-6 rounded-lg bg-blue-50 text-blue-600 hover:bg-blue-100 text-xs font-semibold transition-colors">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Transaksi
                </a>
            </div>
        </div>

        {{-- Right Column: Details & History --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Detail Info Card --}}
            <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm" style="box-shadow: 0 4px 40px rgba(0,0,0,0.04);">
                <div class="px-6 py-5 border-b border-slate-50 flex items-center justify-between">
                    <h2 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Informasi Produk</h2>
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold
                                 {{ $product->is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-50 text-slate-500 border border-slate-200' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-6 text-sm">
                        <div>
                            <dt class="text-xs font-bold text-slate-400 mb-1">SKU</dt>
                            <dd class="font-mono font-medium text-slate-700">{{ $product->sku ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 mb-1">Kategori</dt>
                            <dd class="font-medium text-slate-700">
                                @if($product->category)
                                    <span class="px-2 py-1 bg-blue-50 text-blue-600 rounded-md">{{ $product->category->name }}</span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <dt class="text-xs font-bold text-slate-400 mb-1">Harga Jual</dt>
                            <dd class="text-xl font-extrabold text-emerald-600">Rp {{ number_format($product->price, 0, ',', '.') }}</dd>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                            <dt class="text-xs font-bold text-slate-400 mb-1">Harga Modal</dt>
                            <dd class="text-lg font-bold text-slate-700">Rp {{ number_format($product->cost, 0, ',', '.') }}</dd>
                            <dd class="mt-1 text-xs font-bold text-emerald-500">Margin: {{ $product->marginPercent() }}%</dd>
                        </div>
                        @if($product->description)
                        <div class="col-span-2 pt-2">
                            <dt class="text-xs font-bold text-slate-400 mb-1">Deskripsi</dt>
                            <dd class="text-slate-600 leading-relaxed font-medium bg-slate-50 p-4 rounded-2xl border border-slate-100">{{ $product->description }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Riwayat Transaksi --}}
            @can('view-ledger')
            <div class="bg-white rounded-3xl border border-slate-100 overflow-hidden shadow-sm" style="box-shadow: 0 4px 40px rgba(0,0,0,0.04);">
                <div class="px-6 py-5 border-b border-slate-50">
                    <h2 class="text-sm font-bold text-slate-400 uppercase tracking-widest">Riwayat Transaksi Terkait</h2>
                </div>
                <div class="divide-y divide-slate-50">
                    @forelse($product->ledgers as $l)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 {{ $l->type === 'income' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($l->type === 'income')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    @endif
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-700">{{ $l->title }}</p>
                                <p class="text-xs font-semibold text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-sm font-extrabold {{ $l->type === 'income' ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                            </span>
                            @if($l->quantity)
                                <p class="text-xs font-bold text-slate-400 mt-0.5">{{ $l->quantity }} {{ $product->unit }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-10 flex flex-col items-center justify-center text-center">
                        <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center mb-3">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-500">Belum ada transaksi</p>
                        <p class="text-xs font-medium text-slate-400 mt-1">Transaksi untuk produk ini akan muncul di sini.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
