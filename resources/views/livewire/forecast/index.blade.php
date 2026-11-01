<?php

use App\Models\{Location, Supplier};
use App\Services\ForecastService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    // Horizon = jumlah hari kebutuhan yang ingin dipenuhi setelah restock
    public int $horizonDays = 30;

    // Panjang riwayat penjualan yang dipakai untuk peramalan
    public int $historyDays = 90;

    public string $search = '';
    public string $statusFilter = '';
    public array $selected = [];
    public string $location_id = '';
    public string $supplier_id = '';

    public function mount(): void
    {
        $this->authorize('view-forecast');
    }

    public function getForecastProperty(): array
    {
        return (new ForecastService())->run($this->horizonDays, $this->historyDays);
    }

    public function getRowsProperty(): array
    {
        $rows = $this->forecast['results'];

        if ($this->search !== '') {
            $keyword = mb_strtolower($this->search);
            $rows = array_values(array_filter($rows, fn (array $row) =>
                str_contains(mb_strtolower($row['name']), $keyword)
                || str_contains(mb_strtolower((string) $row['sku']), $keyword)
                || str_contains(mb_strtolower($row['category']), $keyword)
            ));
        }

        if ($this->statusFilter !== '') {
            $rows = array_values(array_filter($rows, fn (array $row) => $row['status'] === $this->statusFilter));
        }

        return $rows;
    }

    public function getSummaryProperty(): array
    {
        return $this->forecast['summary'];
    }

    public function getLocationsProperty()
    {
        return Location::where('is_active', true)->orderBy('name')->get();
    }

    public function getSuppliersProperty()
    {
        return Supplier::where('is_active', true)->whereNull('deleted_at')->orderBy('name')->get();
    }

    public function getSelectedCostProperty(): float
    {
        $rows = collect($this->forecast['results'])->keyBy('id');
        $total = 0.0;

        foreach ($this->selected as $id) {
            $total += (float) ($rows->get((int) $id)['estimated_cost'] ?? 0);
        }

        return $total;
    }

    public function updatedHorizonDays(): void
    {
        $this->horizonDays = min(365, max(1, (int) $this->horizonDays));
        $this->selected = [];
    }

    public function updatedHistoryDays(): void
    {
        $this->historyDays = min(365, max(14, (int) $this->historyDays));
        $this->selected = [];
    }

    public function toggleSelectAll(): void
    {
        $selectable = array_map(
            fn (array $row) => (string) $row['id'],
            array_filter($this->rows, fn (array $row) => $row['recommended_qty'] > 0)
        );

        $this->selected = count(array_intersect($this->selected, $selectable)) === count($selectable) && count($selectable) > 0
            ? []
            : $selectable;
    }

    public function createDrafts(): void
    {
        $this->authorize('create-purchase_request');

        $this->validate([
            'selected'    => ['required', 'array', 'min:1'],
            'selected.*'  => ['integer'],
            'location_id' => ['required', Rule::exists('locations', 'id')->where('is_active', true)],
            'supplier_id' => ['nullable', Rule::exists('suppliers', 'id')->where('is_active', true)->whereNull('deleted_at')],
        ], [
            'selected.required'  => 'Pilih minimal satu produk yang perlu direstock.',
            'location_id.required' => 'Pilih lokasi gudang tujuan terlebih dahulu.',
        ]);

        $result = (new ForecastService())->createDraftRequests(
            $this->selected,
            (int) $this->location_id,
            $this->supplier_id !== '' ? (int) $this->supplier_id : null,
            $this->horizonDays,
            $this->historyDays
        );

        $this->selected = [];

        if ($result['created'] > 0) {
            session()->flash(
                'success',
                "{$result['created']} draft Purchase Request dibuat dan menunggu persetujuan."
                . ($result['skipped'] > 0 ? " {$result['skipped']} produk dilewati (sudah ada PR pending atau tidak perlu restock)." : '')
            );
        } else {
            session()->flash(
                'warning',
                'Tidak ada draft yang dibuat. Semua produk terpilih sudah memiliki PR pending atau tidak memerlukan restock.'
            );
        }
    }
}; ?>

<div class="space-y-6 max-w-6xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Predictive Analytics</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#2f6b5c] rounded-lg">Ensemble + Safety Stock</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 8-8M21 7v6h-6"/>
                </svg>
                Forecast Restock
            </h1>
            <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-1 max-w-2xl">
                Prakiraan permintaan harian dengan <span class="text-[#17343d] font-extrabold">Single Exponential Smoothing</span>,
                lalu hitung <span class="text-[#2f6b5c] font-extrabold">reorder point</span> dan jumlah beli yang disarankan.
            </p>
        </div>

        {{-- Kontrol Periode --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <div class="bg-white border border-[#dce1de]/80 rounded-lg px-3 py-2.5 shadow-none">
                <p class="text-[10px] uppercase font-extrabold text-[#71817f] tracking-wider">Proyeksi</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <input wire:model.live.debounce.500ms="horizonDays" type="number" min="1" max="365"
                           class="w-14 px-1 text-sm font-extrabold text-[#172c35] bg-[#f5f6f3] border border-[#dce1de] rounded-lg text-center focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]" />
                    <span class="text-xs font-bold text-[#315b50]">hari</span>
                </div>
            </div>
            <div class="bg-white border border-[#dce1de]/80 rounded-lg px-3 py-2.5 shadow-none">
                <p class="text-[10px] uppercase font-extrabold text-[#71817f] tracking-wider">Riwayat</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <input wire:model.live.debounce.500ms="historyDays" type="number" min="14" max="365"
                           class="w-14 px-1 text-sm font-extrabold text-[#172c35] bg-[#f5f6f3] border border-[#dce1de] rounded-lg text-center focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]" />
                    <span class="text-xs font-bold text-[#64747a]">hari</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-[#b7d4ce] bg-[#e8efeb] p-3 text-sm font-semibold text-[#2f6b5c]">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="rounded-lg border border-[#ddcba7] bg-[#f7f1e4] p-3 text-sm font-semibold text-[#5e4820]">{{ session('warning') }}</div>
    @endif

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-white rounded-lg border border-[#ead6d4]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Kritis</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#9c5252] tracking-tight">{{ $this->summary['kritis'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f7eceb] text-[#9c5252]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Stok habis sebelum lead time</p>
        </div>

        <div class="bg-white rounded-lg border border-[#ddcba7]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Perlu Restock</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#5e4820] tracking-tight">{{ $this->summary['perhatian'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f7f1e4] text-[#5e4820]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Stok menyentuh reorder point</p>
        </div>

        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Estimasi Biaya</span>
                    <p class="mt-2 text-lg sm:text-2xl font-extrabold text-[#17343d] tracking-tight">Rp {{ number_format($this->summary['estimated_cost'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#f5f6f3] text-[#315b50]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Total saran beli × HPP</p>
        </div>

        <div class="bg-white rounded-lg border border-[#b7d4ce]/80 p-4 sm:p-5 shadow-none">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-[#64747a]">Akurasi (MAPE)</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-[#2f6b5c] tracking-tight">
                        {{ $this->summary['average_mape'] === null ? '—' : number_format($this->summary['average_mape'], 1, ',', '.') . '%' }}
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-[#e8efeb] text-[#2f6b5c]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-[#e6eae7] text-[10px] font-semibold text-[#71817f]">Rata-rata error peramalan</p>
        </div>
    </div>

    {{-- ── Toolbar ── --}}
    <div class="rounded-lg border border-[#dce1de]/80 bg-white p-4 shadow-none space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center flex-1">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari produk, SKU, atau kategori"
                       class="min-w-0 flex-1 rounded-lg border-[#cbd3d0] text-sm focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]" />
                <select wire:model.live="statusFilter" class="rounded-lg border-[#cbd3d0] text-sm sm:w-48 focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]">
                    <option value="">Semua status</option>
                    <option value="kritis">Kritis</option>
                    <option value="perhatian">Perlu Restock</option>
                    <option value="aman">Aman</option>
                    <option value="tanpa_penjualan">Tanpa Penjualan</option>
                </select>
            </div>

            @can('create-purchase_request')
            <div class="flex justify-end">
                <button type="button" wire:click="createDrafts"
                        wire:confirm="Buat draft Purchase Request untuk {{ count($selected) }} produk terpilih?"
                        @disabled(count($selected) === 0 || $location_id === '')
                        class="inline-flex items-center gap-2 rounded-lg bg-[#244e49] px-4 py-2.5 text-sm font-bold text-white hover:bg-[#17343d] disabled:cursor-not-allowed disabled:bg-[#dce1de]">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Buat Draft PR
                    @if (count($selected) > 0)
                        <span class="rounded-full bg-white/20 px-1.5 text-[10px] font-extrabold">{{ count($selected) }}</span>
                    @endif
                </button>
            </div>
            @endcan
        </div>

        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-sm">
            <label class="block">
                <span class="text-[10px] uppercase font-extrabold text-[#71817f] tracking-wider">Lokasi tujuan</span>
                <select wire:model.live="location_id" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]">
                    <option value="">Pilih lokasi gudang</option>
                    @foreach ($this->locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('location_id')<span class="mt-1 block text-xs text-[#9c5252]">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="text-[10px] uppercase font-extrabold text-[#71817f] tracking-wider">Supplier (opsional)</span>
                <select wire:model.live="supplier_id" class="mt-1 w-full rounded-lg border-[#cbd3d0] text-sm focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70]">
                    <option value="">Belum ditentukan</option>
                    @foreach ($this->suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<span class="mt-1 block text-xs text-[#9c5252]">{{ $message }}</span>@enderror
            </label>

            <div class="rounded-lg bg-[#f5f6f3] border border-[#dce1de]/60 px-3 py-2">
                <p class="text-[10px] uppercase font-extrabold text-[#71817f] tracking-wider">Produk terpilih</p>
                <p class="mt-0.5 text-sm font-extrabold text-[#344950]">{{ count($selected) }} produk</p>
            </div>

            <div class="rounded-lg bg-[#f5f6f3] border border-[#dce1de]/60 px-3 py-2">
                <p class="text-[10px] uppercase font-extrabold text-[#527c70] tracking-wider">Estimasi nilai draft</p>
                <p class="mt-0.5 text-sm font-extrabold text-[#17343d]">Rp {{ number_format($this->selectedCost, 0, ',', '.') }}</p>
            </div>
        </div>

        @error('selected')<div class="rounded-lg bg-[#f7eceb] px-3 py-2 text-xs font-semibold text-[#9c5252]">{{ $message }}</div>@enderror
    </div>

    {{-- ── Tabel Forecast ── --}}
    <div class="overflow-hidden rounded-lg border border-[#dce1de] bg-white shadow-none">
        <div class="flex flex-col gap-2 px-4 py-3 border-b border-[#e6eae7] sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-[#172c35]">Prakiraan per Produk</h2>
                <p class="text-[11px] font-medium text-[#64747a]">
                    {{ count($this->rows) }} produk ditampilkan · riwayat {{ $historyDays }} hari · α = {{ $this->forecast['alpha'] }}
                </p>
            </div>
            <button type="button" wire:click="toggleSelectAll"
                    class="self-start rounded-lg border border-[#dce1de] px-2.5 py-1.5 text-[11px] font-bold text-[#52646a] hover:bg-[#f5f6f3]">
                Pilih semua yang perlu restock
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="bg-[#f5f6f3] text-[10px] uppercase text-[#64747a]">
                    <tr>
                        <th class="px-3 py-3 w-10"></th>
                        <th class="px-3 py-3">Produk</th>
                        <th class="px-3 py-3 text-right">Stok</th>
                        <th class="px-3 py-3 text-right">Laju / hari</th>
                        <th class="px-3 py-3 text-right">Lead Time</th>
                        <th class="px-3 py-3 text-right">Sisa Hari</th>
                        <th class="px-3 py-3 text-right">ROP</th>
                        <th class="px-3 py-3 text-right">Proyeksi {{ $horizonDays }}h</th>
                        <th class="px-3 py-3 text-right">Saran Beli</th>
                        <th class="px-3 py-3 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y border-[#e6eae7]">
                    @forelse ($this->rows as $row)
                        @php
                            $badge = match ($row['status']) {
                                'kritis' => 'bg-[#f7eceb] text-[#9c5252]',
                                'perhatian' => 'bg-[#f7f1e4] text-[#5e4820]',
                                'tanpa_penjualan' => 'bg-[#eef2ee] text-[#64747a]',
                                default => 'bg-[#e8efeb] text-[#2f6b5c]',
                            };
                            $label = match ($row['status']) {
                                'kritis' => 'Kritis',
                                'perhatian' => 'Perlu Restock',
                                'tanpa_penjualan' => 'Tanpa Penjualan',
                                default => 'Aman',
                            };
                        @endphp
                        <tr wire:key="forecast-{{ $row['id'] }}" class="hover:bg-[#f5f6f3]/60 {{ $row['status'] === 'kritis' ? 'bg-[#f7eceb]/30' : '' }}">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $row['id'] }}" wire:model.live="selected"
                                       @disabled($row['recommended_qty'] <= 0)
                                       class="rounded border-[#cbd3d0] text-[#315b50] focus:ring-[#527c70] disabled:opacity-30" />
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-bold text-[#172c35]">{{ $row['name'] }}</div>
                                <div class="text-[11px] text-[#71817f]">
                                    {{ $row['category'] }}
                                    @if ($row['sku']) · {{ $row['sku'] }} @endif
                                </div>
                                <div class="text-[10px] text-[#71817f] mt-0.5">
                                    Terjual {{ number_format($row['total_sold'], 0, ',', '.') }} {{ $row['unit'] }}
                                    @if ($row['mape'] !== null) · MAPE {{ number_format($row['mape'], 1, ',', '.') }}% @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold text-[#344950]">
                                {{ number_format($row['stock'], 0, ',', '.') }}
                                <div class="text-[10px] font-medium text-[#71817f]">min {{ $row['min_stock'] }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-[#344950]">
                                {{ number_format($row['daily_rate'], 2, ',', '.') }}
                                <div class="text-[10px] text-[#71817f]">MA7 {{ number_format($row['ma_7'], 2, ',', '.') }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-[#344950]">
                                {{ $row['lead_time'] }} <span class="text-[10px] text-[#71817f]">hari</span>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold {{ $row['days_remaining'] !== null && $row['days_remaining'] <= $row['lead_time_used'] ? 'text-[#9c5252]' : 'text-[#344950]' }}">
                                {{ $row['days_remaining'] === null ? '—' : number_format($row['days_remaining'], 1, ',', '.') }}
                                @if ($row['days_remaining'] !== null)<div class="text-[10px] font-medium text-[#71817f]">hari</div>@endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-[#344950]">
                                {{ number_format($row['reorder_point'], 0, ',', '.') }}
                                <div class="text-[10px] text-[#71817f]">SS {{ number_format($row['safety_stock'], 0, ',', '.') }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-[#344950]">
                                {{ number_format($row['forecast_demand'], 0, ',', '.') }}
                                <div class="text-[10px] {{ $row['projected_stock'] < 0 ? 'text-[#9c5252] font-bold' : 'text-[#71817f]' }}">
                                    sisa {{ number_format($row['projected_stock'], 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                @if ($row['recommended_qty'] > 0)
                                    <div class="font-extrabold text-[#17343d] tabular-nums">+{{ number_format($row['recommended_qty'], 0, ',', '.') }} {{ $row['unit'] }}</div>
                                    <div class="text-[10px] text-[#71817f]">Rp {{ number_format($row['estimated_cost'], 0, ',', '.') }}</div>
                                @else
                                    <span class="text-[#a9beb5]">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $badge }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $row['status'] === 'kritis' ? 'bg-[#9c5252]' : ($row['status'] === 'perhatian' ? 'bg-[#8a6a2f]' : ($row['status'] === 'aman' ? 'bg-[#527c70]' : 'bg-[#a9beb5]')) }}"></span>
                                    {{ $label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-[#71817f]">
                                Tidak ada produk yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Footer Metode ── --}}
    <div class="rounded-lg border border-[#dce1de]/80 bg-white p-4 shadow-none">
        <p class="text-[10px] uppercase font-extrabold tracking-wider text-[#71817f] mb-2">Metode Perhitungan</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-[11px] text-[#52646a]">
            <div class="rounded-lg bg-[#f5f6f3] p-3">
                <span class="font-extrabold text-[#344950]">Laju harian (ensemble)</span>
                <p class="mt-1">50% rata-rata historis + 30% MA30 + 20% SES (α = {{ $this->forecast['alpha'] }})</p>
            </div>
            <div class="rounded-lg bg-[#f5f6f3] p-3">
                <span class="font-extrabold text-[#344950]">Safety stock</span>
                <p class="mt-1">SS = z · σ · √lead time, z = {{ $this->forecast['z_score'] }} (service level {{ $this->forecast['service_level'] }}%)</p>
            </div>
            <div class="rounded-lg bg-[#f5f6f3] p-3">
                <span class="font-extrabold text-[#344950]">Reorder point</span>
                <p class="mt-1">ROP = laju harian × lead time + safety stock</p>
            </div>
            <div class="rounded-lg bg-[#f5f6f3] p-3">
                <span class="font-extrabold text-[#344950]">Saran beli</span>
                <p class="mt-1">(laju × (lead time + {{ $horizonDays }} hari) + SS) − stok saat ini</p>
            </div>
        </div>
    </div>

</div>
