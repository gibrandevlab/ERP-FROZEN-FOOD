<?php

use App\Models\Ledger;
use App\Models\LedgerItem;
use App\Models\Location;
use App\Models\Stock;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {
    public string $selectedLocation = '';

    public function mount(): void
    {
        $this->authorize('view-stok');
        $this->selectedLocation = (string) (request()->query('lokasi') ?: Location::where('is_active', true)->value('id') ?: '');
    }

    public function getLocationListProperty()
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    public function getSelectedLocationModelProperty(): ?Location
    {
        return $this->selectedLocation
            ? Location::where('is_active', true)->find($this->selectedLocation)
            : null;
    }

    public function getStocksProperty()
    {
        if (! $this->selectedLocationModel) {
            return collect();
        }

        return Stock::with('product.category')
            ->where('location_id', $this->selectedLocationModel->id)
            ->where('quantity', '>', 0)
            ->orderBy('product_id')
            ->get();
    }

    public function getItemMovementsProperty()
    {
        if (! $this->selectedLocationModel) {
            return collect();
        }

        return LedgerItem::with(['ledger.supplier', 'ledger.customer', 'product'])
            ->where('location_id', $this->selectedLocationModel->id)
            ->whereHas('ledger', fn ($query) => $query->whereNull('deleted_at'))
            ->latest('id')
            ->limit(100)
            ->get();
    }

    public function getLegacyMovementsProperty()
    {
        if (! $this->selectedLocationModel) {
            return collect();
        }

        return Ledger::with(['product', 'supplier', 'customer'])
            ->where('location_id', $this->selectedLocationModel->id)
            ->whereNotNull('stock_movement')
            ->latest('date')
            ->latest('id')
            ->limit(100)
            ->get();
    }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#eef2ee] via-white to-[#f5f6f3] p-5 sm:p-6 flex items-center justify-between gap-4">
        <div>
            <span class="inline-flex px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#dce1de]/80 text-[#315b50] rounded-lg">Inventaris</span>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1">Stok per Lokasi</h1>
            <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Lihat isi lokasi, mutasi, dan detail pembelian berdasarkan invoice.</p>
        </div>
        @can('create-pembukuan')
            <a href="{{ route('pembukuan.tambah') }}" wire:navigate class="px-4 py-2.5 rounded-lg text-xs font-extrabold text-white bg-[#244e49]">Catat Transaksi</a>
        @endcan
    </div>

    <div class="flex gap-2 border-b border-[#dce1de]">
        <a href="{{ route('stok.index') }}" wire:navigate class="px-4 py-2.5 text-xs font-bold border-b-2 border-transparent text-[#64747a] hover:text-[#315b50]">Daftar Produk</a>
        <a href="{{ route('stok.lokasi') }}" wire:navigate class="px-4 py-2.5 text-xs font-extrabold border-b-2 border-[#315b50] text-[#315b50]">Stok per Lokasi</a>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-end gap-3">
        <div class="flex-1">
            <label for="selected-location" class="block text-xs font-bold text-[#52646a] mb-1.5 uppercase tracking-wider">Pilih lokasi</label>
            <select id="selected-location" wire:model.live="selectedLocation" class="w-full sm:max-w-md px-4 py-3 bg-white border border-[#dce1de] rounded-lg text-sm font-bold text-[#344950]">
                <option value="">— Belum ada lokasi aktif —</option>
                @foreach($this->locationList as $location)
                    <option value="{{ $location->id }}">{{ $location->name }}</option>
                @endforeach
            </select>
        </div>
        @if($this->selectedLocationModel)
            <div class="text-xs font-semibold text-[#64747a]">
                {{ $this->stocks->count() }} produk · {{ $this->stocks->sum('quantity') }} unit
            </div>
        @endif
    </div>

    @if($this->selectedLocationModel)
        <section class="rounded-lg border border-[#dce1de]/80 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e6eae7]">
                <h2 class="font-extrabold text-[#172c35]">{{ $this->selectedLocationModel->name }}</h2>
                @if($this->selectedLocationModel->description)
                    <p class="text-xs text-[#64747a] mt-1">{{ $this->selectedLocationModel->description }}</p>
                @endif
            </div>
            <div class="divide-y divide-[#e6eae7]">
                @forelse($this->stocks as $stock)
                    <div class="flex items-center justify-between gap-4 px-5 py-4">
                        <div class="min-w-0">
                            <a href="{{ route('stok.detail', $stock->product->slug) }}" wire:navigate class="font-extrabold text-sm text-[#172c35] hover:text-[#315b50]">{{ $stock->product->name }}</a>
                            <p class="text-xs text-[#71817f] mt-0.5">{{ $stock->product->category?->name ?? 'Tanpa kategori' }} @if($stock->product->sku) · {{ $stock->product->sku }} @endif</p>
                        </div>
                        <p class="shrink-0 text-lg font-extrabold text-[#315b50]">{{ number_format($stock->quantity) }} <span class="text-xs text-[#71817f]">{{ $stock->product->unit }}</span></p>
                    </div>
                @empty
                    <p class="px-5 py-8 text-center text-sm font-semibold text-[#71817f]">Belum ada stok di lokasi ini.</p>
                @endforelse
            </div>
        </section>

        <section class="rounded-lg border border-[#dce1de]/80 bg-white overflow-hidden">
            <div class="px-5 py-4 border-b border-[#e6eae7]">
                <h2 class="font-extrabold text-[#172c35]">Riwayat Stok & Detail Invoice</h2>
                <p class="text-xs text-[#64747a] mt-1">Transaksi terbaru yang menambah atau mengurangi stok di lokasi ini.</p>
            </div>
            <div class="divide-y divide-[#e6eae7]">
                @forelse($this->itemMovements as $item)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-extrabold text-sm text-[#172c35]">{{ $item->product_name }}</p>
                                <p class="text-xs text-[#64747a] mt-1">
                                    {{ $item->ledger->title }} · {{ $item->ledger->date->format('d M Y') }}
                                    @if($item->ledger->supplier) · Supplier: {{ $item->ledger->supplier->name }} @endif
                                    @if($item->ledger->customer) · Pelanggan: {{ $item->ledger->customer->name }} @endif
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="font-extrabold text-sm {{ $item->ledger->type === 'expense' ? 'text-[#315b50]' : 'text-[#8a6a2f]' }}">
                                    {{ $item->ledger->type === 'expense' ? '+' : '-' }}{{ number_format($item->quantity) }} {{ $item->product?->unit ?? 'unit' }}
                                </p>
                                <p class="text-xs text-[#64747a] mt-1">Rp {{ number_format((float) $item->unit_price * $item->quantity, 0, ',', '.') }}</p>
                            </div>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-[#64747a]">
                            <span>Invoice: {{ $item->ledger->reference ?: '—' }}</span>
                            <span>Harga/unit: Rp {{ number_format((float) $item->unit_price, 0, ',', '.') }}</span>
                            <span>{{ $item->ledger->payment_status === 'paid' ? 'Lunas' : 'Belum lunas' }}</span>
                        </div>
                    </div>
                @empty
                    @if($this->legacyMovements->isEmpty())
                        <p class="px-5 py-8 text-center text-sm font-semibold text-[#71817f]">Belum ada transaksi stok untuk lokasi ini.</p>
                    @endif
                @endforelse

                @foreach($this->legacyMovements as $movement)
                    <div class="px-5 py-4">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <p class="font-extrabold text-sm text-[#172c35]">{{ $movement->product?->name ?? $movement->title }}</p>
                                <p class="text-xs text-[#64747a] mt-1">{{ $movement->title }} · {{ $movement->date->format('d M Y') }} @if($movement->supplier) · Supplier: {{ $movement->supplier->name }} @endif</p>
                            </div>
                            <p class="font-extrabold text-sm text-[#64747a]">{{ $movement->stock_movement === 'in' ? '+' : '-' }}{{ number_format($movement->quantity) }}</p>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] font-semibold text-[#64747a]">
                            <span>Invoice: {{ $movement->reference ?: '—' }}</span>
                            <span>Nominal: Rp {{ number_format((float) $movement->amount, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @elseif($this->locationList->isEmpty())
        <div class="rounded-lg border border-dashed border-[#cbd3d0] bg-white p-8 text-center">
            <p class="font-bold text-[#344950]">Belum ada lokasi aktif.</p>
            @can('create-lokasi')
                <a href="{{ route('lokasi.tambah') }}" wire:navigate class="mt-3 inline-block text-sm font-extrabold text-[#315b50]">Tambah lokasi</a>
            @endcan
        </div>
    @endif
</div>
