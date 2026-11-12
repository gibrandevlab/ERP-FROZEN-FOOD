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
            ->withSum(['stocks as stock_quantity' => fn ($query) => $query
                ->when($this->filterLokasi, fn ($stockQuery) => $stockQuery->where('location_id', $this->filterLokasi))], 'quantity')
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
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#eef2ee] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#dce1de]/80 text-[#315b50] rounded-lg">Katalog Inventaris</span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                Stok Produk
            </h1>
            <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">{{ $this->products->total() }} produk terdaftar dalam sistem.</p>
        </div>

            <a href="{{ route('stok.tambah') }}" wire:navigate @click="playClick()"
           class="btn-sound inline-flex items-center justify-center gap-2 px-5 py-3 rounded-lg text-xs sm:text-sm font-extrabold text-white bg-[#244e49] hover:bg-[#173d38] shadow-none shadow-[#244e49]/15 transition-all shrink-0 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Produk</span>
        </a>
    </div>

    <div class="flex gap-2 border-b border-[#dce1de]">
        <a href="{{ route('stok.index') }}" wire:navigate class="px-4 py-2.5 text-xs font-extrabold border-b-2 border-[#315b50] text-[#315b50]">Daftar Produk</a>
        <a href="{{ route('stok.lokasi') }}" wire:navigate class="px-4 py-2.5 text-xs font-bold border-b-2 border-transparent text-[#64747a] hover:text-[#315b50]">Stok per Lokasi</a>
    </div>

    {{-- ── Filter Bar ── --}}
    <div class="flex flex-wrap items-center gap-2.5">
        <div class="relative flex-1 min-w-[200px]">
            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-[#71817f]">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Cari nama atau SKU produk..."
                   class="w-full pl-10 pr-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] placeholder:text-[#71817f] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] transition-all shadow-none" />
        </div>

        <select wire:model.live="filterLokasi"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Lokasi</option>
            @foreach($this->locationList as $loc)
                <option value="{{ $loc->id }}">{{ $loc->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterKat"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Kategori</option>
            @foreach($this->kategoriList as $k)
                <option value="{{ $k->id }}">{{ $k->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterAktif"
                class="px-3.5 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-medium text-[#344950] focus:ring-2 focus:ring-[#527c70]/25 focus:border-[#527c70] shadow-none transition-all">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>
    </div>

    {{-- ── Mobile Layout Cards ── --}}
    <div class="space-y-3.5 sm:hidden">
        @forelse($this->products as $p)
        @php($stokProduk = (int) ($p->stock_quantity ?? 0))
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 shadow-none space-y-3">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="font-extrabold text-[#172c35] text-sm truncate">{{ $p->name }}</p>
                    @if($p->sku)<p class="text-xs text-[#71817f] font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    <p class="text-xs text-[#64747a] font-medium mt-0.5">{{ $p->category?->name ?? '—' }}</p>
                </div>
                <span class="shrink-0 inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $p->is_active ? 'bg-[#e8efeb] text-[#2f6b5c] border border-[#d3e0da]' : 'bg-[#e6eae7] text-[#71817f] border border-[#dce1de]' }}">
                    {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                </span>
            </div>

            <div class="flex items-center justify-between pt-2 border-t border-[#e6eae7]">
                <div class="flex items-center gap-3 text-xs">
                    <span class="font-extrabold {{ $stokProduk < 10 ? 'text-[#8a6a2f]' : 'text-[#344950]' }}">
                        {{ $stokProduk }} {{ $p->unit }}
                    </span>
                    <span class="text-[#64747a] font-semibold">Rp {{ number_format($p->price, 0, ',', '.') }}</span>
                </div>

                <div class="flex items-center gap-1.5">
                    <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound px-2.5 py-1.5 rounded-md bg-[#f5f6f3] text-[#52646a] text-xs font-bold border border-[#dce1de]/80 hover:bg-[#e6eae7] transition-all">Detail</a>
                    <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate @click="playClick()"
                       class="btn-sound p-1.5 rounded-md bg-[#e8efeb] text-[#315b50] border border-[#dce1de] hover:bg-[#dce1de] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </a>
                    <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" @click="playDanger()"
                            class="btn-sound p-1.5 rounded-md bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4] hover:bg-[#ead6d4] transition-all">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-8 text-center shadow-none">
            <p class="text-[#71817f] text-xs sm:text-sm font-semibold">Belum ada produk yang ditemukan.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Desktop Table ── --}}
    <div class="hidden sm:block bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#f5f6f3]/80 border-b border-[#dce1de]/80 text-[11px] font-extrabold text-[#64747a] uppercase tracking-wider">
                    <th class="px-6 py-4">Detail Produk</th>
                    <th class="px-6 py-4 hidden lg:table-cell">Kategori</th>
                    <th class="px-6 py-4 text-right">Stok</th>
                    <th class="px-6 py-4 text-right hidden md:table-cell">Harga Jual</th>
                    <th class="px-6 py-4 text-center">Status</th>
                    <th class="px-6 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#e6eae7] text-xs sm:text-sm font-medium text-[#344950]">
                @forelse($this->products as $p)
                @php($stokProduk = (int) ($p->stock_quantity ?? 0))
                <tr class="hover:bg-[#e8efeb]/30 transition-colors">
                    <td class="px-6 py-4">
                        <p class="font-extrabold text-[#172c35]">{{ $p->name }}</p>
                        @if($p->sku)<p class="text-xs text-[#71817f] font-mono mt-0.5">{{ $p->sku }}</p>@endif
                    </td>
                    <td class="px-6 py-4 text-[#64747a] font-medium hidden lg:table-cell">{{ $p->category?->name ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="font-extrabold {{ $stokProduk < 10 ? 'text-[#8a6a2f]' : 'text-[#344950]' }}">
                            {{ $stokProduk }}
                        </span>
                        <span class="text-xs text-[#71817f] ml-1 font-semibold">{{ $p->unit }}</span>
                    </td>
                    <td class="px-6 py-4 text-right font-bold text-[#344950] hidden md:table-cell">
                        Rp {{ number_format($p->price, 0, ',', '.') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider
                                     {{ $p->is_active ? 'bg-[#e8efeb] text-[#2f6b5c] border border-[#d3e0da]' : 'bg-[#e6eae7] text-[#71817f] border border-[#dce1de]' }}">
                            {{ $p->is_active ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('stok.detail', $p->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound px-3 py-1.5 rounded-md bg-[#f5f6f3] text-[#52646a] text-xs font-bold border border-[#dce1de]/80 hover:bg-[#e6eae7] transition-all">Detail</a>
                            <a href="{{ route('stok.edit', $p->slug) }}" wire:navigate @click="playClick()"
                               class="btn-sound p-2 rounded-md bg-[#e8efeb] text-[#315b50] border border-[#dce1de] hover:bg-[#dce1de] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </a>
                            <button wire:click="hapus({{ $p->id }})" wire:confirm="Hapus produk '{{ $p->name }}'?" @click="playDanger()"
                                    class="btn-sound p-2 rounded-md bg-[#f7eceb] text-[#8f4a4a] border border-[#ead6d4] hover:bg-[#ead6d4] transition-all">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-[#71817f] text-xs sm:text-sm font-semibold">
                        @if($search || $filterKat || $filterLokasi || $filterAktif !== '')
                            Tidak ada produk yang cocok dengan filter.
                            <button wire:click="$set('search',''); $set('filterKat',''); $set('filterLokasi',''); $set('filterAktif','')"
                                    class="ml-2 text-[#315b50] hover:underline font-bold">Reset filter</button>
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
