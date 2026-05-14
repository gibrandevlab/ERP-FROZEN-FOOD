<?php

use App\Services\SpkService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    // Default target restock untuk 30 hari ke depan
    public $targetDays = 30;

    public function getSpkDataProperty(): array
    {
        $target = max(1, (int) $this->targetDays);
        return (new SpkService())->run($target);
    }

    public function getKritisCountProperty(): int
    {
        return count(array_filter($this->spkData['results'], fn($r) => $r['priority'] === 'kritis'));
    }

    public function getPerhatianCountProperty(): int
    {
        return count(array_filter($this->spkData['results'], fn($r) => $r['priority'] === 'perhatian'));
    }

    public function getAmanCountProperty(): int
    {
        return count(array_filter($this->spkData['results'], fn($r) => $r['priority'] === 'aman'));
    }
}; ?>

<div class="space-y-5 max-w-3xl mx-auto lg:max-w-none">

    {{-- ── Header ─────────────────────────────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-extrabold" style="color:#1E293B;">🧠 SPK Prioritas Restock</h1>
            <p class="text-xs text-slate-500 mt-0.5">Urutan rekomendasi berdasarkan <span class="font-bold">Algoritma Entropy + SAW</span></p>
        </div>
        {{-- Input Target Hari --}}
        <div class="flex items-center bg-white border border-slate-200 rounded-xl px-3 py-1.5 shadow-sm flex-shrink-0">
            <label class="text-xs font-semibold text-slate-500 mr-2">Beli untuk stok:</label>
            <input wire:model.live.debounce.500ms="targetDays" type="number" min="1" max="365"
                   class="w-16 px-2 py-1 text-sm font-bold text-slate-800 bg-slate-50 border-none rounded-lg focus:ring-2 focus:ring-blue-500 text-center" />
            <span class="text-xs font-semibold text-slate-500 ml-2">hari</span>
        </div>
    </div>

    @if($this->spkData['total_products'] === 0)
    {{-- ── Empty State ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-100 p-10 text-center shadow-sm">
        <p class="text-3xl mb-2">📦</p>
        <p class="font-semibold text-slate-600 text-sm">Belum ada data produk aktif</p>
        <p class="text-xs text-slate-400 mt-1">Tambah produk dan catat transaksi penjualan terlebih dahulu.</p>
    </div>

    @else

    {{-- ── Mini KPI: Ringkasan Status ──────────────────────────────────────── --}}
    <div class="grid grid-cols-3 gap-3">
        {{-- Kritis --}}
        <div class="bg-white rounded-2xl border border-red-100 px-4 py-3.5 shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-red-50 rounded-xl flex items-center justify-center flex-shrink-0 text-lg">🔴</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Kritis</p>
                    <p class="text-xl font-bold text-red-600 leading-tight">{{ $this->kritisCount }}</p>
                </div>
            </div>
        </div>
        {{-- Perhatian --}}
        <div class="bg-white rounded-2xl border border-amber-100 px-4 py-3.5 shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-amber-50 rounded-xl flex items-center justify-center flex-shrink-0 text-lg">🟡</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Perhatian</p>
                    <p class="text-xl font-bold text-amber-600 leading-tight">{{ $this->perhatianCount }}</p>
                </div>
            </div>
        </div>
        {{-- Aman --}}
        <div class="bg-white rounded-2xl border border-emerald-100 px-4 py-3.5 shadow-sm">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 bg-emerald-50 rounded-xl flex items-center justify-center flex-shrink-0 text-lg">🟢</div>
                <div>
                    <p class="text-xs text-slate-500 font-medium">Aman</p>
                    <p class="text-xl font-bold text-emerald-600 leading-tight">{{ $this->amanCount }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Daftar Produk ────────────────────────────────────────────────────── --}}
    <div class="space-y-3">
        @foreach($this->spkData['results'] as $item)
        @php
            $isKritis    = $item['priority'] === 'kritis';
            $isPerhatian = $item['priority'] === 'perhatian';
            $hasBuy      = $item['recommended_buy'] > 0;

            $accentColor = $isKritis ? 'bg-red-400' : ($isPerhatian ? 'bg-amber-400' : 'bg-emerald-400');
            $borderColor = $isKritis ? 'border-slate-100' : ($isPerhatian ? 'border-slate-100' : 'border-slate-100');
            $badgeCls    = $isKritis
                ? 'bg-red-50 text-red-600'
                : ($isPerhatian ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-700');
            $rankCls     = $isKritis
                ? 'bg-red-100 text-red-600'
                : ($isPerhatian ? 'bg-amber-100 text-amber-600' : 'bg-slate-100 text-slate-500');
            $buyColor    = $isKritis ? 'text-red-600' : 'text-amber-600';
        @endphp

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden"
             style="box-shadow: 0 2px 16px rgba(0,0,0,0.04), 0 1px 4px rgba(0,0,0,0.03);">

            {{-- Left accent bar --}}
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $accentColor }}"></div>

            {{-- Baris utama --}}
            <div class="flex items-start justify-between gap-3 px-5 py-4 pl-6">
                {{-- Kiri: rank + nama --}}
                <div class="flex items-start gap-3 min-w-0">
                    <span class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-[11px] font-bold {{ $rankCls }}">
                        {{ $item['rank'] }}
                    </span>
                    <div class="min-w-0">
                        <p class="font-bold text-slate-800 text-sm truncate">{{ $item['name'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5 truncate">
                            {{ $item['category'] }}
                            @if($item['sku'])<span class="font-mono ml-1">· {{ $item['sku'] }}</span>@endif
                        </p>
                    </div>
                </div>

                {{-- Kanan: rekomendasi beli (info terpenting) --}}
                <div class="text-right flex-shrink-0">
                    @if($hasBuy)
                        <p class="text-base font-extrabold {{ $buyColor }}">
                            +{{ number_format($item['recommended_buy'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">{{ $item['unit'] }}</span>
                        </p>
                        <p class="text-[10px] text-slate-400 mt-0.5">beli lagi</p>
                    @else
                        <p class="text-sm font-bold text-emerald-600">Stok cukup</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">tidak perlu beli</p>
                    @endif
                </div>
            </div>

            {{-- Baris statistik --}}
            <div class="flex items-center gap-0 border-t border-slate-50 divide-x divide-slate-50">
                {{-- Stok sekarang --}}
                <div class="flex-1 px-4 py-2 pl-6 text-center">
                    <p class="text-[10px] text-slate-400 font-medium">Sisa Stok</p>
                    <p class="text-sm font-bold {{ $item['c1_stok'] <= 5 ? 'text-red-500' : 'text-slate-700' }} mt-0.5">
                        {{ number_format($item['c1_stok'], 0) }}
                        <span class="text-xs font-normal text-slate-400">{{ $item['unit'] }}</span>
                    </p>
                    <p class="text-[10px] font-semibold mt-0.5 {{ $isKritis ? 'text-red-500' : ($isPerhatian ? 'text-amber-500' : 'text-emerald-500') }}">
                        {{ $item['sisa_hari'] == 999 ? '> 30 hari' : '~' . $item['sisa_hari'] . ' hari' }}
                    </p>
                </div>
                {{-- Terjual (30 hari) --}}
                <div class="flex-1 px-4 py-2 text-center border-l border-slate-50">
                    <p class="text-[10px] text-slate-400 font-medium">Terjual (30hr)</p>
                    <p class="text-sm font-bold text-slate-700 mt-0.5">
                        {{ number_format($item['c2_terjual'], 0) }}
                        <span class="text-xs font-normal text-slate-400">{{ $item['unit'] }}</span>
                    </p>
                </div>
                {{-- Rata-rata/hari --}}
                <div class="flex-1 px-4 py-2 text-center">
                    <p class="text-[10px] text-slate-400 font-medium">Rata-rata/hari</p>
                    <p class="text-sm font-bold text-slate-700 mt-0.5">
                        {{ $item['daily_rate'] > 0 ? number_format($item['daily_rate'], 1) : '—' }}
                        @if($item['daily_rate'] > 0)
                        <span class="text-xs font-normal text-slate-400">{{ $item['unit'] }}</span>
                        @endif
                    </p>
                </div>
                {{-- Status --}}
                <div class="px-4 py-2 text-center">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $badgeCls }}">
                        {{ $isKritis ? '🔴 Kritis' : ($isPerhatian ? '🟡 Perhatian' : '🟢 Aman') }}
                    </span>
                </div>
            </div>

            {{-- Banner rekomendasi (hanya jika harus beli) --}}
            @if($hasBuy)
            <div class="border-t border-dashed {{ $isKritis ? 'border-red-100 bg-red-50/50' : 'border-amber-100 bg-amber-50/40' }} px-5 py-2 pl-6">
                <p class="text-xs {{ $isKritis ? 'text-red-700' : 'text-amber-700' }}">
                    💡 <span class="font-bold">Beli {{ number_format($item['recommended_buy'], 0) }} {{ $item['unit'] }}</span>
                    — untuk stok <span class="font-bold">{{ $targetDays }} hari</span> ke depan
                    @if($item['daily_rate'] > 0)
                        <span class="text-slate-400">(~{{ number_format($item['daily_rate'], 1) }}/hari)</span>
                    @endif
                </p>
            </div>
            @endif

        </div>
        @endforeach
    </div>

    {{-- ── Footer ───────────────────────────────────────────────────────────── --}}
    <p class="text-center text-[10px] text-slate-300 pb-2">
        Dihitung dari: rata-rata harian (30 hari terakhir) × {{ $targetDays }} hari target − stok sekarang
    </p>

    @endif

</div>
