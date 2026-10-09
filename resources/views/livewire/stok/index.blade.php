<?php

use App\Models\{Product, Category, Location};
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search       = '';
    public string $filterKat    = '';
    public string $filterAktif  = '';
    public string $filterLokasi = '';

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

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Katalog Inventaris</span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Stok Produk
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">{{ $this->products->total() }} produk terdaftar dalam sistem.</p>
        </div>

        <a href="{{ route('stok.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl text-xs sm:text-sm font-extrabold text-white bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20 transition-all shrink-0 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Produk</span>
        </a>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau SKU produk..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all shadow-sm" />
        </div>

        <select wire:model.live="filterLokasi"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Lokasi</option>
            @foreach($this->locationList as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterKat"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Kategori</option>
            @foreach($this->kategoriList as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterAktif"
                class="px-3.5 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-medium text-slate-700 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse($this->products as $p)
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold text-slate-900 text-sm truncate">{{ $p->name }}</p>
                    @if($p->sku)<p class="text-xs text-slate-400 font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    <p class="text-xs text-slate-500 font-medium mt-0.5">{{ $p->category?->name ?? '—' }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $p->is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-slate-100">
                <div class="flex items-center gap-3 text-xs">
                    <span class="font-extrabold {{ ($filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock()) < 10 ? 'text-amber-600' : 'text-slate-800' }}">
                        {{ $filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock() }} {{ $p->unit }}
                    </span>
                    <span class="text-slate-500 font-semibold">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                </div>

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound px-2.5 py-1.5 rounded-xl bg-slate-50 text-slate-600 text-xs font-bold border border-slate-200/80 hover:bg-slate-100 transition-all">Detail</a>
                    <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound p-1.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" @click="playDanger()"
                            class="btn-sound p-1.5 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-2xl border border-slate-200/80 p-8 text-center shadow-sm">
            <p class="text-slate-400 text-xs sm:text-sm font-semibold">Belum ada produk yang ditemukan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Detail Produk</th>
                    <th class="px-6 py-4 hidden lg:table-cell">Kategori</th>
                    <th class="px-6 py-4 text-right">Stok</th>
                    <th class="px-6 py-4 text-right hidden md:table-cell">Harga Jual</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @forelse($this->products as $p)
                <tr class="hover:bg-blue-50/30 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-extrabold text-slate-900">{{ $p->name }}</p>
                        @if($p->sku)<p class="text-xs text-slate-400 font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    </td>
                    <td class="px-6 py-4 text-slate-500 font-medium hidden lg:table-cell">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-extrabold {{ ($filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock()) < 10 ? 'text-amber-600' : 'text-slate-800' }}">
                            {{ $filterLokasi ? $p->stockAt($filterLokasi) : $p->totalStock() }}
                        </span>
                        <span class="text-xs text-slate-400 ml-1 font-semibold">{{ $p->unit }}</span>
                    </td>
                    <td class="px-6 py-4 text-right font-bold text-slate-800 hidden md:table-cell">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                     {{ $p->is_active ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-slate-100 text-slate-400 border border-slate-200' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-3 py-1.5 rounded-xl bg-slate-50 text-slate-600 text-xs font-bold border border-slate-200/80 hover:bg-slate-100 transition-all">Detail</a>
                            <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound p-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 hover:bg-blue-100 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" @click="playDanger()"
                                    class="btn-sound p-2 rounded-xl bg-rose-50 text-rose-600 border border-rose-100 hover:bg-rose-100 transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-400 text-xs sm:text-sm font-semibold">
                        @if($search || $filterKat || $filterLokasi || $filterAktif !== '')
                            Tidak ada produk yang cocok dengan filter.
                            <button wire:click="$set('search',''); $set('filterKat',''); $set('filterLokasi',''); $set('filterAktif','')"
                                    class="ml-2 text-blue-600 hover:underline font-bold">Reset filter</button>
                        @else
                            Belum ada produk. Tambahkan produk pertama Anda!
                        @endif
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="pt-2">
        {{ $this->products->links() }}
    </div>
</div>
