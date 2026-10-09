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

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Sistem Cerdas</span>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-indigo-100/80 text-indigo-700 rounded-lg">Prioritas Restock</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 01-2 2h-1a2 2 0 01-2-2v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                SPK Prioritas Restock
            </h1>
            <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5 max-w-xl">
                Analisis peramalan stok menggunakan bobot kriteria <span class="text-blue-700 font-extrabold">Entropy</span> dan perankingan alternatif <span class="text-indigo-700 font-extrabold">Simple Additive Weighting (SAW)</span>.
            </p>
        </div>

        {{-- Control Target Hari --}}
        <div class="flex items-center bg-white border border-slate-200/80 rounded-2xl p-3 shadow-sm shrink-0 self-start sm:self-auto">
            <div class="mr-3">
                <p class="text-[10px] uppercase font-extrabold text-slate-400 tracking-wider">Target Proyeksi</p>
                <p class="text-xs font-bold text-slate-700">Restock Barang</p>
            </div>
            <div class="flex items-center bg-slate-50 rounded-xl px-2.5 py-1.5 border border-slate-200 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition-all">
                <input wire:model.live.debounce.500ms="targetDays" type="number" min="1" max="365"
                       class="w-12 px-1 text-xs sm:text-sm font-extrabold text-slate-900 bg-transparent border-none outline-none focus:ring-0 text-center" />
                <span class="text-xs font-bold text-blue-600 ml-1">hari</span>
            </div>
        </div>
    </div>

    @if($this->spkData['total_products'] === 0)
    {{-- ── Empty State ── --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 p-12 text-center shadow-sm">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center mb-4">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        </div>
        <p class="font-extrabold text-slate-900 text-sm sm:text-base">Belum ada data produk aktif</p>
        <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto font-medium">Untuk memulai analisis, silakan daftarkan produk aktif dan catat transaksi penjualan terlebih dahulu.</p>
    </div>

    @else

    {{-- ── KPI Cards Status Restock ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Kritis Card --}}
        <div class="bg-white rounded-3xl border border-rose-200/80 p-5 shadow-sm flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-rose-50 text-rose-600 rounded-2xl flex items-center justify-center shrink-0 border border-rose-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-rose-700 uppercase tracking-wider">Status Kritis</p>
                    <p class="text-[10px] text-slate-400 font-medium">Stok segera habis</p>
                </div>
            </div>
            <span class="text-2xl sm:text-3xl font-extrabold text-rose-600">{{ $this->kritisCount }}</span>
        </div>

        {{-- Perhatian Card --}}
        <div class="bg-white rounded-3xl border border-amber-200/80 p-5 shadow-sm flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center shrink-0 border border-amber-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-amber-700 uppercase tracking-wider">Status Perhatian</p>
                    <p class="text-[10px] text-slate-400 font-medium">Perlu dipantau</p>
                </div>
            </div>
            <span class="text-2xl sm:text-3xl font-extrabold text-amber-600">{{ $this->perhatianCount }}</span>
        </div>

        {{-- Aman Card --}}
        <div class="bg-white rounded-3xl border border-emerald-200/80 p-5 shadow-sm flex items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shrink-0 border border-emerald-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                </div>
                <div>
                    <p class="text-xs font-extrabold text-emerald-700 uppercase tracking-wider">Status Aman</p>
                    <p class="text-[10px] text-slate-400 font-medium">Stok masih mencukupi</p>
                </div>
            </div>
            <span class="text-2xl sm:text-3xl font-extrabold text-emerald-600">{{ $this->amanCount }}</span>
        </div>
    </div>

    {{-- ── Bobot Dinamis Entropy ── --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 p-6 shadow-sm space-y-4">
        <div class="flex items-center gap-3">
            <div class="w-9 h-9 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center shrink-0 border border-blue-100">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
            </div>
            <div>
                <h3 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider">Pembobotan Kriteria Dinamis (Metode Entropy)</h3>
                <p class="text-[11px] text-slate-400 font-medium mt-0.5">Bobot dihitung secara otomatis berdasarkan penyebaran data variasi alternatif saat ini.</p>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 flex flex-col justify-between hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">C1: Sisa Stok (Cost)</span>
                <span class="text-base sm:text-lg font-extrabold text-blue-600 mt-1">{{ number_format(($this->spkData['weights']['c1_stok'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Entropy: {{ number_format($this->spkData['entropy']['c1_stok'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 flex flex-col justify-between hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">C2: Penjualan (Benefit)</span>
                <span class="text-base sm:text-lg font-extrabold text-blue-600 mt-1">{{ number_format(($this->spkData['weights']['c2_terjual'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Entropy: {{ number_format($this->spkData['entropy']['c2_terjual'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 flex flex-col justify-between hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">C3: Perputaran (Benefit)</span>
                <span class="text-base sm:text-lg font-extrabold text-blue-600 mt-1">{{ number_format(($this->spkData['weights']['c3_perputaran'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Entropy: {{ number_format($this->spkData['entropy']['c3_perputaran'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/80 p-3.5 rounded-2xl border border-slate-200/80 flex flex-col justify-between hover:bg-slate-100/80 transition-colors">
                <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">C4: Lead Time (Cost)</span>
                <span class="text-base sm:text-lg font-extrabold text-blue-600 mt-1">{{ number_format(($this->spkData['weights']['c4_lead_time'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5 font-medium">Entropy: {{ number_format($this->spkData['entropy']['c4_lead_time'] ?? 0, 4) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Subtitle Rekomendasi ── --}}
    <div class="flex items-center justify-between border-b border-slate-200/80 pb-3 pt-2">
        <h2 class="text-xs sm:text-sm font-extrabold text-slate-900 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
            Daftar Rekomendasi Urutan Restock
        </h2>
        <span class="text-xs text-slate-400 font-bold">{{ count($this->spkData['results']) }} Produk Dianalisis</span>
    </div>

    {{-- ── Lista Hasil Analisis SPK ── --}}
    <div class="space-y-4">
        @foreach($this->spkData['results'] as $item)
        @php
            $isKritis    = $item['priority'] === 'kritis';
            $isPerhatian = $item['priority'] === 'perhatian';
            $hasBuy      = $item['recommended_buy'] > 0;

            $accentColor = $isKritis
                ? 'bg-rose-500'
                : ($isPerhatian ? 'bg-amber-500' : 'bg-emerald-500');

            $badgeCls    = $isKritis
                ? 'bg-rose-50 text-rose-600 border border-rose-100'
                : ($isPerhatian ? 'bg-amber-50 text-amber-600 border border-amber-100' : 'bg-emerald-50 text-emerald-600 border border-emerald-100');

            $rankCls = 'bg-slate-100 text-slate-600 border border-slate-200';
            if ($item['rank'] == 1) {
                $rankCls = 'bg-amber-500 text-white border border-amber-400 font-extrabold shadow-sm';
            } elseif ($item['rank'] == 2) {
                $rankCls = 'bg-slate-300 text-slate-800 border border-slate-200 font-bold';
            } elseif ($item['rank'] == 3) {
                $rankCls = 'bg-amber-700 text-white border border-amber-600 font-bold';
            }

            $buyColor = $isKritis ? 'text-rose-600' : 'text-amber-600';
            $buyBadge = $isKritis ? 'bg-rose-50 border-rose-100' : 'bg-amber-50 border-amber-100';
        @endphp

        <div class="bg-white rounded-3xl border border-slate-200/80 shadow-sm relative overflow-hidden transition-all duration-200 hover:shadow-md">

            {{-- Accent Line Kiri --}}
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $accentColor }}"></div>

            <div class="p-5 pl-7 space-y-4">
                {{-- Info Utama & Rekomendasi Beli --}}
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5 min-w-0">
                        {{-- Rank --}}
                        <span class="mt-0.5 shrink-0 w-7 h-7 rounded-xl flex items-center justify-center text-xs font-extrabold {{ $rankCls }}">
                            {{ $item['rank'] }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-slate-900 text-sm sm:text-base truncate">{{ $item['name'] }}</h3>
                            <div class="flex items-center gap-1.5 mt-1 flex-wrap">
                                <span class="text-xs text-slate-500 font-bold bg-slate-100 px-2.5 py-0.5 rounded-lg">{{ $item['category'] }}</span>
                                @if($item['sku'])
                                    <span class="text-[10px] font-mono text-slate-400 bg-slate-50 px-2 py-0.5 border border-slate-200/80 rounded-lg">SKU: {{ $item['sku'] }}</span>
                                @endif
                                <span class="text-[10px] font-extrabold text-blue-600 bg-blue-50 px-2.5 py-0.5 border border-blue-100 rounded-lg">Skor SAW: {{ number_format($item['score'], 4) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Output Rekomendasi Beli --}}
                    <div class="text-right shrink-0">
                        @if($hasBuy)
                            <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-2xl border {{ $buyBadge }} {{ $buyColor }} font-extrabold text-xs sm:text-sm shadow-sm">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                                <span>{{ number_format($item['recommended_buy'], 0) }} {{ $item['unit'] }}</span>
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-extrabold mt-1">Rekomendasi Beli</p>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-2xl bg-emerald-50 text-emerald-600 font-extrabold text-xs border border-emerald-100">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span>Stok Cukup</span>
                            </div>
                            <p class="text-[10px] uppercase tracking-wider text-slate-400 font-extrabold mt-1">Tidak Perlu Beli</p>
                        @endif
                    </div>
                </div>

                {{-- Metric Grid --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-3 border-t border-slate-100">
                    {{-- Sisa Stok (C1) --}}
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-center flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider">Sisa Stok (C1)</p>
                        <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">
                            {{ number_format($item['c1_stok'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">{{ $item['unit'] }}</span>
                        </p>
                        <p class="text-[9px] font-extrabold mt-1 {{ $isKritis ? 'text-rose-600' : ($isPerhatian ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ $item['sisa_hari'] == 999 ? 'Tersisa > 30 hari' : '~' . $item['sisa_hari'] . ' hari' }}
                        </p>
                    </div>

                    {{-- Terjual (C2) --}}
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-center flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider">Terjual (C2)</p>
                        <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">
                            {{ number_format($item['c2_terjual'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">{{ $item['unit'] }}</span>
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Laju: {{ $item['daily_rate'] > 0 ? number_format($item['daily_rate'], 1) : '0' }}/hari</p>
                    </div>

                    {{-- Perputaran Stok (C3) --}}
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-center flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider">Perputaran (C3)</p>
                        <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">
                            {{ number_format($item['c3_perputaran'], 2) }}
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Rasio C2/C1</p>
                    </div>

                    {{-- Lead Time Supplier (C4) --}}
                    <div class="bg-slate-50 p-3 rounded-2xl border border-slate-200/60 text-center flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-extrabold tracking-wider">Lead Time (C4)</p>
                        <p class="text-sm sm:text-base font-extrabold text-slate-800 mt-1">
                            {{ number_format($item['c4_lead_time'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">hari</span>
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Waktu Kirim</p>
                    </div>

                    {{-- Badge Status Prioritas --}}
                    <div class="flex items-center justify-center p-3 col-span-2 sm:col-span-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider {{ $badgeCls }}">
                            <span class="w-2 h-2 rounded-full {{ $isKritis ? 'bg-rose-500' : ($isPerhatian ? 'bg-amber-500' : 'bg-emerald-500') }}"></span>
                            {{ $isKritis ? 'Kritis' : ($isPerhatian ? 'Perhatian' : 'Aman') }}
                        </span>
                    </div>
                </div>

                {{-- Catatan Saran Pengadaan --}}
                @if($hasBuy)
                <div class="rounded-2xl border border-dashed p-3.5 flex items-center gap-2.5 {{ $isKritis ? 'border-rose-200 bg-rose-50/50 text-rose-900' : 'border-amber-200 bg-amber-50/50 text-amber-900' }}">
                    <svg class="w-4 h-4 shrink-0 {{ $isKritis ? 'text-rose-600' : 'text-amber-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <p class="text-xs font-medium leading-relaxed">
                        Saran pengadaan: <span class="font-extrabold underline">Beli {{ number_format($item['recommended_buy'], 0) }} {{ $item['unit'] }}</span>
                        untuk menjaga ketersediaan hingga <span class="font-extrabold">{{ $targetDays }} hari</span> ke depan
                        @if($item['daily_rate'] > 0)
                            (berdasarkan laju penjualan ~{{ number_format($item['daily_rate'], 1) }} {{ $item['unit'] }}/hari).
                        @endif
                    </p>
                </div>
                @endif
            </div>

        </div>
        @endforeach
    </div>

    {{-- ── Footer Rumus SPK ── --}}
    <div class="flex flex-col items-center justify-center gap-1 text-[10px] text-slate-400 font-bold uppercase tracking-wider pt-4 text-center">
        <div>Rumus Proyeksi Beli: <span class="text-slate-600">(Laju Harian × Target Hari) − Sisa Stok</span></div>
        <div class="mt-0.5">Normalisasi SAW: <span class="text-slate-600">C1, C4 (Cost: min/x), C2, C3 (Benefit: x/max)</span></div>
    </div>

    @endif

</div>
