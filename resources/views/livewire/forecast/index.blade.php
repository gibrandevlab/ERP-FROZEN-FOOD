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
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Predictive Analytics</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-emerald-100/80 text-emerald-700 rounded-lg">Ensemble + Safety Stock</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M3 17l6-6 4 4 8-8M21 7v6h-6"/>
                </svg>
                Forecast Restock
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-1 max-w-2xl">
                Prakiraan permintaan harian dengan <span class="text-blue-700 font-extrabold">Single Exponential Smoothing</span>,
                lalu hitung <span class="text-emerald-700 font-extrabold">reorder point</span> dan jumlah beli yang disarankan.
            </p>
        </div>

        {{-- Kontrol Periode --}}
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            <div class="bg-white border border-slate-200/80 rounded-2xl px-3 py-2.5 shadow-sm">
                <p class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Proyeksi</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <input wire:model.live.debounce.500ms="horizonDays" type="number" min="1" max="365"
                           class="w-14 px-1 text-sm font-extrabold text-slate-900 bg-slate-50 border border-slate-200 rounded-lg text-center focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                    <span class="text-xs font-bold text-blue-600">hari</span>
                </div>
            </div>
            <div class="bg-white border border-slate-200/80 rounded-2xl px-3 py-2.5 shadow-sm">
                <p class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Riwayat</p>
                <div class="flex items-center gap-1.5 mt-0.5">
                    <input wire:model.live.debounce.500ms="historyDays" type="number" min="14" max="365"
                           class="w-14 px-1 text-sm font-extrabold text-slate-900 bg-slate-50 border border-slate-200 rounded-lg text-center focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                    <span class="text-xs font-bold text-slate-500">hari</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{{ session('success') }}</div>
    @endif
    @if (session('warning'))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">{{ session('warning') }}</div>
    @endif

    {{-- ── KPI Cards ── --}}
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-3.5 sm:gap-4">
        <div class="bg-white rounded-2xl border border-rose-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500">Kritis</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-rose-600 tracking-tight">{{ $this->summary['kritis'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-slate-50 text-[10px] font-semibold text-slate-400">Stok habis sebelum lead time</p>
        </div>

        <div class="bg-white rounded-2xl border border-amber-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500">Perlu Restock</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-amber-600 tracking-tight">{{ $this->summary['perhatian'] }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-slate-50 text-[10px] font-semibold text-slate-400">Stok menyentuh reorder point</p>
        </div>

        <div class="bg-white rounded-2xl border border-blue-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500">Estimasi Biaya</span>
                    <p class="mt-2 text-lg sm:text-2xl font-extrabold text-blue-700 tracking-tight">Rp {{ number_format($this->summary['estimated_cost'], 0, ',', '.') }}</p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-slate-50 text-[10px] font-semibold text-slate-400">Total saran beli × HPP</p>
        </div>

        <div class="bg-white rounded-2xl border border-emerald-200/80 p-4 sm:p-5 shadow-sm">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <span class="text-xs font-bold text-slate-500">Akurasi (MAPE)</span>
                    <p class="mt-2 text-2xl sm:text-3xl font-extrabold text-emerald-600 tracking-tight">
                        {{ $this->summary['average_mape'] === null ? '—' : number_format($this->summary['average_mape'], 1, ',', '.') . '%' }}
                    </p>
                </div>
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
            </div>
            <p class="mt-3 pt-2 border-t border-slate-50 text-[10px] font-semibold text-slate-400">Rata-rata error peramalan</p>
        </div>
    </div>

    {{-- ── Toolbar ── --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm space-y-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center flex-1">
                <input wire:model.live.debounce.300ms="search" type="search" placeholder="Cari produk, SKU, atau kategori"
                       class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500" />
                <select wire:model.live="statusFilter" class="rounded-xl border-slate-300 text-sm sm:w-48 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
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
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:bg-slate-300">
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
                <span class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Lokasi tujuan</span>
                <select wire:model.live="location_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="">Pilih lokasi gudang</option>
                    @foreach ($this->locations as $location)
                        <option value="{{ $location->id }}">{{ $location->name }}</option>
                    @endforeach
                </select>
                @error('location_id')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>

            <label class="block">
                <span class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Supplier (opsional)</span>
                <select wire:model.live="supplier_id" class="mt-1 w-full rounded-xl border-slate-300 text-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500">
                    <option value="">Belum ditentukan</option>
                    @foreach ($this->suppliers as $supplier)
                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                    @endforeach
                </select>
                @error('supplier_id')<span class="mt-1 block text-xs text-rose-600">{{ $message }}</span>@enderror
            </label>

            <div class="rounded-xl bg-slate-50 border border-slate-200/60 px-3 py-2">
                <p class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Produk terpilih</p>
                <p class="mt-0.5 text-sm font-extrabold text-slate-800">{{ count($selected) }} produk</p>
            </div>

            <div class="rounded-xl bg-blue-50 border border-blue-200/60 px-3 py-2">
                <p class="text-[10px] uppercase font-extrabold text-blue-500 tracking-wider">Estimasi nilai draft</p>
                <p class="mt-0.5 text-sm font-extrabold text-blue-800">Rp {{ number_format($this->selectedCost, 0, ',', '.') }}</p>
            </div>
        </div>

        @error('selected')<div class="rounded-lg bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700">{{ $message }}</div>@enderror
    </div>

    {{-- ── Tabel Forecast ── --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-col gap-2 px-4 py-3 border-b border-slate-100 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-extrabold text-slate-900">Prakiraan per Produk</h2>
                <p class="text-[11px] font-medium text-slate-500">
                    {{ count($this->rows) }} produk ditampilkan · riwayat {{ $historyDays }} hari · α = {{ $this->forecast['alpha'] }}
                </p>
            </div>
            <button type="button" wire:click="toggleSelectAll"
                    class="self-start rounded-lg border border-slate-200 px-2.5 py-1.5 text-[11px] font-bold text-slate-600 hover:bg-slate-50">
                Pilih semua yang perlu restock
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full min-w-[1080px] text-left text-sm">
                <thead class="bg-slate-50 text-[10px] uppercase text-slate-500">
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
                <tbody class="divide-y divide-slate-100">
                    @forelse ($this->rows as $row)
                        @php
                            $badge = match ($row['status']) {
                                'kritis' => 'bg-rose-50 text-rose-700',
                                'perhatian' => 'bg-amber-50 text-amber-700',
                                'tanpa_penjualan' => 'bg-slate-100 text-slate-500',
                                default => 'bg-emerald-50 text-emerald-700',
                            };
                            $label = match ($row['status']) {
                                'kritis' => 'Kritis',
                                'perhatian' => 'Perlu Restock',
                                'tanpa_penjualan' => 'Tanpa Penjualan',
                                default => 'Aman',
                            };
                        @endphp
                        <tr wire:key="forecast-{{ $row['id'] }}" class="hover:bg-slate-50/60 {{ $row['status'] === 'kritis' ? 'bg-rose-50/30' : '' }}">
                            <td class="px-3 py-3">
                                <input type="checkbox" value="{{ $row['id'] }}" wire:model.live="selected"
                                       @disabled($row['recommended_qty'] <= 0)
                                       class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 disabled:opacity-30" />
                            </td>
                            <td class="px-3 py-3">
                                <div class="font-bold text-slate-900">{{ $row['name'] }}</div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $row['category'] }}
                                    @if ($row['sku']) · {{ $row['sku'] }} @endif
                                </div>
                                <div class="text-[10px] text-slate-400 mt-0.5">
                                    Terjual {{ number_format($row['total_sold'], 0, ',', '.') }} {{ $row['unit'] }}
                                    @if ($row['mape'] !== null) · MAPE {{ number_format($row['mape'], 1, ',', '.') }}% @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold text-slate-700">
                                {{ number_format($row['stock'], 0, ',', '.') }}
                                <div class="text-[10px] font-medium text-slate-400">min {{ $row['min_stock'] }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">
                                {{ number_format($row['daily_rate'], 2, ',', '.') }}
                                <div class="text-[10px] text-slate-400">MA7 {{ number_format($row['ma_7'], 2, ',', '.') }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">
                                {{ $row['lead_time'] }} <span class="text-[10px] text-slate-400">hari</span>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums font-semibold {{ $row['days_remaining'] !== null && $row['days_remaining'] <= $row['lead_time_used'] ? 'text-rose-600' : 'text-slate-700' }}">
                                {{ $row['days_remaining'] === null ? '—' : number_format($row['days_remaining'], 1, ',', '.') }}
                                @if ($row['days_remaining'] !== null)<div class="text-[10px] font-medium text-slate-400">hari</div>@endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">
                                {{ number_format($row['reorder_point'], 0, ',', '.') }}
                                <div class="text-[10px] text-slate-400">SS {{ number_format($row['safety_stock'], 0, ',', '.') }}</div>
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700">
                                {{ number_format($row['forecast_demand'], 0, ',', '.') }}
                                <div class="text-[10px] {{ $row['projected_stock'] < 0 ? 'text-rose-500 font-bold' : 'text-slate-400' }}">
                                    sisa {{ number_format($row['projected_stock'], 0, ',', '.') }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-right">
                                @if ($row['recommended_qty'] > 0)
                                    <div class="font-extrabold text-blue-700 tabular-nums">+{{ number_format($row['recommended_qty'], 0, ',', '.') }} {{ $row['unit'] }}</div>
                                    <div class="text-[10px] text-slate-400">Rp {{ number_format($row['estimated_cost'], 0, ',', '.') }}</div>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wide {{ $badge }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $row['status'] === 'kritis' ? 'bg-rose-500' : ($row['status'] === 'perhatian' ? 'bg-amber-500' : ($row['status'] === 'aman' ? 'bg-emerald-500' : 'bg-slate-400')) }}"></span>
                                    {{ $label }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-4 py-12 text-center text-sm text-slate-400">
                                Tidak ada produk yang cocok dengan filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ── Footer Metode ── --}}
    <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-sm">
        <p class="text-[10px] uppercase font-extrabold tracking-wider text-slate-400 mb-2">Metode Perhitungan</p>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4 text-[11px] text-slate-600">
            <div class="rounded-xl bg-slate-50 p-3">
                <span class="font-extrabold text-slate-700">Laju harian (ensemble)</span>
                <p class="mt-1">50% rata-rata historis + 30% MA30 + 20% SES (α = {{ $this->forecast['alpha'] }})</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <span class="font-extrabold text-slate-700">Safety stock</span>
                <p class="mt-1">SS = z · σ · √lead time, z = {{ $this->forecast['z_score'] }} (service level {{ $this->forecast['service_level'] }}%)</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <span class="font-extrabold text-slate-700">Reorder point</span>
                <p class="mt-1">ROP = laju harian × lead time + safety stock</p>
            </div>
            <div class="rounded-xl bg-slate-50 p-3">
                <span class="font-extrabold text-slate-700">Saran beli</span>
                <p class="mt-1">(laju × (lead time + {{ $horizonDays }} hari) + SS) − stok saat ini</p>
            </div>
        </div>
    </div>

</div>
