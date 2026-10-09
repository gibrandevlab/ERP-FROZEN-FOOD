<?php

use App\Models\{Product, Ledger};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;

new #[Layout('layouts.app')] class extends Component {

    public Product $product;

    public function mount(string $slug): void
    {
        $this->authorize('view-stok');
        $this->product = Product::with(['category', 'ledgers' => fn($q) => $q->latest()->limit(10)])->where('slug', $slug)->firstOrFail();
    }
}; ?>

<div class="max-w-5xl mx-auto space-y-6">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('stok.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Detail Produk</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1">{{ $product->name }}</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Informasi spesifikasi barang dan riwayat mutasi transaksi.</p>
            </div>
        </div>

        <a href="{{ route('stok.edit', $product->slug) }}" wire:navigate @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-2xl text-xs sm:text-sm font-bold text-slate-700 bg-white border border-slate-200/80 hover:bg-slate-50 transition-all shadow-sm shrink-0 self-start sm:self-auto">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <span>Edit Produk</span>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Image & Stock --}}
        <div class="space-y-6">
            {{-- Image Card --}}
            <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm p-2">
                @if($product->image)
                    <img src="{{ Storage::url($product->image) }}" alt="{{ $product->name }}"
                         class="w-full aspect-square object-cover rounded-2xl" />
                @else
                    <div class="w-full aspect-square bg-slate-50 rounded-2xl flex flex-col items-center justify-center text-slate-400">
                        <svg class="w-16 h-16 mb-2 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs font-semibold">Tidak ada foto produk</span>
                    </div>
                @endif
            </div>

            {{-- Stock Card --}}
            <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm text-center {{ $product->totalStock() < 10 ? 'ring-2 ring-amber-400 border-transparent' : '' }}">
                <p class="text-xs font-extrabold text-slate-400 uppercase tracking-widest mb-1">Total Stok Saat Ini</p>
                <div class="flex items-end justify-center gap-2">
                    <p class="text-5xl sm:text-6xl font-extrabold tracking-tight {{ $product->totalStock() < 10 ? 'text-amber-500' : 'text-slate-900' }}">
                        {{ $product->totalStock() }}
                    </p>
                    <p class="text-base font-bold text-slate-400 mb-1.5">{{ $product->unit }}</p>
                </div>
                @if($product->totalStock() < 10)
                    <div class="mt-4 inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 border border-amber-200 text-amber-700 rounded-full text-xs font-extrabold">
                        <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Stok menipis, segera restock!
                    </div>
                @endif

                <a href="{{ route('pembukuan.tambah', ['produk' => $product->id]) }}" wire:navigate @click="playClick()"
                   class="btn-sound inline-flex items-center justify-center gap-2 w-full px-4 py-3 mt-6 rounded-2xl bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-100 text-xs font-extrabold transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Catat Transaksi Produk
                </a>
            </div>
        </div>

        {{-- Right Column: Details & History --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Detail Info Card --}}
            <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200/80 flex items-center justify-between">
                    <h2 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Informasi Produk</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                 {{ $product->is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                        {{ $product->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
                <div class="p-6">
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-6 text-sm">
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">SKU Produk</dt>
                            <dd class="font-mono font-bold text-slate-800">{{ $product->sku ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</dt>
                            <dd class="font-bold text-slate-800">
                                @if($product->category)
                                    <span class="px-2.5 py-1 bg-blue-50 text-blue-600 rounded-lg text-xs border border-blue-100">{{ $product->category->name }}</span>
                                @else
                                    —
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Lead Time Supplier</dt>
                            <dd class="font-extrabold text-slate-800 flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                <span>{{ $product->lead_time ?? 0 }} Hari</span>
                            </dd>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80">
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Jual</dt>
                            <dd class="text-xl font-extrabold text-emerald-600">Rp {{ number_format($product->price, 0, ',', '.') }}</dd>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200/80 sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Harga Modal & Margin</dt>
                            <div class="flex items-baseline gap-3">
                                <span class="text-lg font-extrabold text-slate-800">Rp {{ number_format($product->cost, 0, ',', '.') }}</span>
                                <span class="text-xs font-extrabold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-100">Margin {{ $product->marginPercent() }}%</span>
                            </div>
                        </div>
                        @if($product->description)
                        <div class="sm:col-span-2">
                            <dt class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Deskripsi Produk</dt>
                            <dd class="text-slate-600 leading-relaxed font-medium bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs sm:text-sm">{{ $product->description }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
            </div>

            {{-- Riwayat Transaksi --}}
            @can('view-pembukuan')
            <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
                <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200/80">
                    <h2 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Riwayat Transaksi Terkait</h2>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($product->ledgers as $l)
                    <div class="px-6 py-4 flex items-center justify-between hover:bg-slate-50/50 transition-colors">
                        <div class="flex items-center gap-3.5">
                            <div class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 {{ $l->type === 'income' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-rose-50 text-rose-600 border border-rose-100' }}">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($l->type === 'income')
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                    @endif
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs sm:text-sm font-extrabold text-slate-900">{{ $l->title }}</p>
                                <p class="text-[11px] font-semibold text-slate-400 mt-0.5">{{ $l->date->format('d M Y') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="text-xs sm:text-sm font-extrabold {{ $l->type === 'income' ? 'text-emerald-600' : 'text-rose-600' }}">
                                {{ $l->type === 'income' ? '+' : '-' }} Rp {{ number_format($l->amount, 0, ',', '.') }}
                            </span>
                            @if($l->quantity)
                                <p class="text-[11px] font-bold text-slate-400 mt-0.5">{{ $l->quantity }} {{ $product->unit }}</p>
                            @endif
                        </div>
                    </div>
                    @empty
                    <div class="px-6 py-10 text-center">
                        <p class="text-xs sm:text-sm font-semibold text-slate-400">Belum ada transaksi terkait produk ini.</p>
                    </div>
                    @endforelse
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>
