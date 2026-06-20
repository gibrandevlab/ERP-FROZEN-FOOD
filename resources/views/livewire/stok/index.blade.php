<?php

use App\Models\{Product, Category, Location};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search      = '';
    public string $filterKat   = '';
    public string $filterAktif = '';
    public string $filterLokasi= '';

    public function mount(): void
    {
        $this->authorize('view-stok');
    }

    public function getKategoriListProperty()
    {
        return Category::orderBy('name')->get();
    }

    public function getLocationListProperty()
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    public function getProductsProperty()
    {
        return Product::with('category')
            ->when($this->search, fn($q) => $q->where(fn($q2) =>
                $q2->where('name', 'like', "%{$this->search}%")
                   ->orWhere('sku', 'like', "%{$this->search}%")
            ))
            ->when($this->filterKat, fn($q) => $q->where('category_id', $this->filterKat))
            ->when($this->filterLokasi, fn($q) => $q->whereHas('stocks', fn($q2) => $q2->where('location_id', $this->filterLokasi)))
            ->when($this->filterAktif !== '', fn($q) => $q->where('is_active', (bool) $this->filterAktif))
            ->latest()
            ->paginate(15);
    }

    public function hapus(int $id): void
    {
        Gate::authorize('delete-stok');
        $p = Product::findOrFail($id);
        $p->delete();
        session()->flash('success', "Produk '{$p->name}' berhasil dihapus.");
    }

    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedFilterKat(): void { $this->resetPage(); }
    public function updatedFilterLokasi(): void { $this->resetPage(); }
    public function updatedFilterAktif(): void { $this->resetPage(); }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-extrabold" style="color: #1E293B;">Stok Produk</h1>
            <p class="text-xs text-slate-500 mt-0.5">{{ $this->products->total() }} produk ditemukan</p>
        </div>
            <a href="{{ route('stok.tambah') }}" wire:navigate
               @click="playClick()"
               class="btn-sound inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-semibold transition-colors shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Produk
            </a>
    </div>

    {{-- ── Filter Bar ──────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-2">
        <input wire:model.live.debounce.300ms="search" type="text" placeholder="🔍 Cari nama atau SKU..."
               class="flex-1 min-w-40 px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 transition-all shadow-sm" />
        <select wire:model.live="filterLokasi"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm">
            <option value="">Semua Lokasi</option>
            @foreach($this->locationList as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterKat"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm">
            <option value="">Semua Kategori</option>
            @foreach($this->kategoriList as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterAktif"
                class="px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>
    </div>

    {{-- ── Mobile: Card List ───────────────────────────────────────────────── --}}
    <div class="space-y-3 sm:hidden">
        @forelse($this->products as $p)
        <div class="bg-white rounded-2xl border border-slate-100 p-4 shadow-sm">
            <div class="flex items-start justify-between gap-3 mb-3">
                <div class="min-w-0">
                    <p class="font-semibold text-slate-800 text-sm truncate">{{ $p->name }}</p>
                    @if($p->sku)<p class="text-xs text-slate-400 font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    <p class="text-xs text-slate-400 mt-0.5">{{ $p->category?->name ?? '—' }}</p>
                </div>
                <span class="flex-shrink-0 px-2.5 py-1 rounded-full text-xs font-semibold {{ $p->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-xs">
                    <span class="font-bold {{ ($filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock()) < 10 ? 'text-orange-500' : 'text-slate-700' }}">
                        {{ $filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock() }} {{ $p->unit }}
                    </span>
                    <span class="text-slate-400">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound px-2.5 py-1 rounded-lg bg-slate-50 text-slate-500 text-xs font-medium hover:bg-slate-100 transition-colors">Detail</a>
                    <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate class="px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-medium hover:bg-slate-200">Edit</a>
                    <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" class="px-2 py-1 bg-red-50 text-red-500 rounded text-xs font-medium hover:bg-red-100">Hapus</button>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-sm">Belum ada produk. Tambahkan yang pertama!</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop: Table ──────────────────────────────────────────────────── --}}
    <div class="hidden sm:block bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Produk</th>
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Kategori</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Stok</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider hidden md:table-cell">Harga Jual</th>
                    <th class="px-5 py-3.5 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @forelse($this->products as $p)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-5 py-4">
                        <p class="font-semibold text-slate-800">{{ $p->name }}</p>
                        @if($p->sku)<p class="text-xs text-slate-400 font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    </td>
                    <td class="px-5 py-4 text-slate-500 hidden lg:table-cell">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-5 py-4 text-right">
                        <span class="font-semibold {{ ($filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock()) < 10 ? 'text-orange-500' : 'text-slate-700' }}">
                            {{ $filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock() }}
                        </span>
                        <span class="text-xs text-slate-400 ml-1">{{ $p->unit }}</span>
                    </td>
                    <td class="px-5 py-4 text-right text-slate-600 hidden md:table-cell">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td class="px-5 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold
                                     {{ $p->is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-400' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-2.5 py-1 rounded-lg bg-slate-50 text-slate-500 text-xs font-medium border border-slate-100 hover:bg-slate-100 transition-colors">Detail</a>
                            <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" class="w-8 h-8 rounded-lg bg-slate-50 flex items-center justify-center text-slate-400 hover:text-red-600 hover:bg-red-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-5 py-12 text-center">
                        <p class="text-slate-400 text-sm">
                            @if($search || $filterKat || $filterLokasi || $filterAktif !== '')
                                Tidak ada produk yang cocok dengan filter.
                                <button wire:click="$set('search',''); $set('filterKat',''); $set('filterLokasi',''); $set('filterAktif','')"
                                        class="ml-2 text-blue-500 hover:underline text-xs">Reset filter</button>
                            @else
                                Belum ada produk. Tambahkan yang pertama!
                            @endif
                        </p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="sticky bottom-4 sm:static mt-6 sm:mt-2 z-40">
        <div class="bg-white/95 sm:bg-transparent backdrop-blur-md sm:backdrop-blur-none border sm:border-0 border-slate-200/60 rounded-2xl sm:rounded-none p-2 sm:p-0 shadow-2xl sm:shadow-none max-w-sm mx-auto sm:max-w-none transition-all">
            {{ $this->products->links() }}
        </div>
    </div>
</div>
