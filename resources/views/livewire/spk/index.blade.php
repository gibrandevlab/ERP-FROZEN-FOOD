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

<<div class="space-y-6 max-w-4xl mx-auto lg:max-w-none">

    {{-- ── Header Card (Premium Gradient Deep Indigo to Dark Purple) ── --}}
    <div class="relative overflow-hidden rounded-3xl p-6 sm:p-8 shadow-xl text-white"
         style="background: linear-gradient(135deg, #0F172A 0%, #1E1B4B 50%, #311042 100%);">
        {{-- Background decorative blobs --}}
        <div class="absolute right-0 top-0 -mt-10 -mr-10 w-48 h-48 rounded-full bg-blue-500/10 blur-3xl"></div>
        <div class="absolute left-1/3 bottom-0 -mb-10 w-64 h-64 rounded-full bg-purple-500/10 blur-3xl"></div>

        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-md">Sistem Cerdas</span>
                    <span class="px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-wider bg-purple-500/20 text-purple-300 border border-purple-500/30 rounded-md">Prioritas Restock</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight">🧠 SPK Prioritas Restock</h1>
                <p class="text-slate-300 text-xs sm:text-sm mt-1 max-w-xl">
                    Sistem cerdas analisis peramalan stok menggunakan kombinasi bobot kriteria <span class="text-indigo-300 font-bold">Entropy</span> dan perangkingan alternatif <span class="text-purple-300 font-bold">Simple Additive Weighting (SAW)</span>.
                </p>
            </div>
            
            {{-- Target Days Control with Glow Effect --}}
            <div class="flex items-center bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-3 shadow-lg shrink-0 self-start sm:self-center">
                <div class="mr-3">
                    <p class="text-[10px] uppercase font-bold text-slate-300">Beli Stok Untuk</p>
                    <p class="text-[10px] text-slate-400">Target Proyeksi</p>
                </div>
                <div class="flex items-center bg-black/20 rounded-xl px-2.5 py-1 border border-white/10 focus-within:ring-2 focus-within:ring-indigo-400 focus-within:border-transparent transition-all">
                    <input wire:model.live.debounce.500ms="targetDays" type="number" min="1" max="365"
                           class="w-12 px-1 text-sm font-black text-white bg-transparent border-none outline-none focus:ring-0 text-center" />
                    <span class="text-xs font-bold text-indigo-300 ml-1">hari</span>
                </div>
            </div>
        </div>
    </div>

    @if($this->spkData['total_products'] === 0)
    {{-- ── Empty State ── --}}
    <div class="bg-white rounded-3xl border border-slate-100 p-12 text-center shadow-lg shadow-slate-100/50">
        <div class="w-16 h-16 mx-auto rounded-full bg-slate-50 flex items-center justify-center text-4xl mb-4 shadow-inner">📦</div>
        <p class="font-extrabold text-slate-800 text-base">Belum ada data produk aktif</p>
        <p class="text-xs text-slate-400 mt-1.5 max-w-sm mx-auto">Untuk memulai analisis, silakan daftarkan produk aktif dan catat transaksi penjualan terlebih dahulu.</p>
    </div>

    @else

    {{-- ── KPI Cards (Curated Harmonious Gradients) ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        {{-- Kritis Card --}}
        <div class="relative overflow-hidden rounded-2xl p-4 border transition-all duration-300 hover:shadow-md"
             style="background: linear-gradient(135deg, #FFF5F5 0%, #FFEBEB 100%); border-color: #FCA5A5;">
            <div class="absolute right-0 bottom-0 opacity-10 translate-x-2 translate-y-2 text-6xl">🚨</div>
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 bg-red-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-red-200 font-bold text-lg">🔴</div>
                <div>
                    <p class="text-xs text-red-700 font-bold uppercase tracking-wider">Status Kritis</p>
                    <p class="text-[10px] text-red-500 font-medium">Stok segera habis</p>
                </div>
                <div class="ml-auto text-3xl font-black text-red-700">{{ $this->kritisCount }}</div>
            </div>
        </div>

        {{-- Perhatian Card --}}
        <div class="relative overflow-hidden rounded-2xl p-4 border transition-all duration-300 hover:shadow-md"
             style="background: linear-gradient(135deg, #FFFDF5 0%, #FFF9DB 100%); border-color: #FDE047;">
            <div class="absolute right-0 bottom-0 opacity-10 translate-x-2 translate-y-2 text-6xl">⚠️</div>
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 bg-amber-400 rounded-xl flex items-center justify-center text-white shadow-lg shadow-amber-200 font-bold text-lg">🟡</div>
                <div>
                    <p class="text-xs text-amber-700 font-bold uppercase tracking-wider">Status Perhatian</p>
                    <p class="text-[10px] text-amber-500 font-medium">Perlu dipantau</p>
                </div>
                <div class="ml-auto text-3xl font-black text-amber-600">{{ $this->perhatianCount }}</div>
            </div>
        </div>

        {{-- Aman Card --}}
        <div class="relative overflow-hidden rounded-2xl p-4 border transition-all duration-300 hover:shadow-md"
             style="background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%); border-color: #86EFAC;">
            <div class="absolute right-0 bottom-0 opacity-10 translate-x-2 translate-y-2 text-6xl">✅</div>
            <div class="flex items-center gap-3.5">
                <div class="w-10 h-10 bg-emerald-500 rounded-xl flex items-center justify-center text-white shadow-lg shadow-emerald-200 font-bold text-lg">🟢</div>
                <div>
                    <p class="text-xs text-emerald-700 font-bold uppercase tracking-wider">Status Aman</p>
                    <p class="text-[10px] text-emerald-500 font-medium">Stok masih mencukupi</p>
                </div>
                <div class="ml-auto text-3xl font-black text-emerald-700">{{ $this->amanCount }}</div>
            </div>
        </div>
    </div>

    {{-- ── Dynamic Weights Card (Entropy) ── --}}
    <div class="bg-white rounded-3xl border border-slate-100 p-5 shadow-lg shadow-slate-100/50 space-y-4">
        <div class="flex items-center gap-2">
            <span class="text-xl">📊</span>
            <div>
                <h3 class="text-sm font-black text-slate-800 uppercase tracking-wider">Pembobotan Kriteria Dinamis (Metode Entropy)</h3>
                <p class="text-[11px] text-slate-400 font-medium">Bobot dihitung secara otomatis berdasarkan penyebaran/keberagaman data alternatif saat ini.</p>
            </div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div class="bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100 flex flex-col justify-between hover:bg-slate-50 transition-colors">
                <span class="text-[10px] font-bold text-slate-400 uppercase">C1: Sisa Stok (Cost)</span>
                <span class="text-lg font-black text-indigo-600 mt-1">{{ number_format(($this->spkData['weights']['c1_stok'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5">Entropy: {{ number_format($this->spkData['entropy']['c1_stok'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100 flex flex-col justify-between hover:bg-slate-50 transition-colors">
                <span class="text-[10px] font-bold text-slate-400 uppercase">C2: Penjualan (Benefit)</span>
                <span class="text-lg font-black text-indigo-600 mt-1">{{ number_format(($this->spkData['weights']['c2_terjual'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5">Entropy: {{ number_format($this->spkData['entropy']['c2_terjual'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100 flex flex-col justify-between hover:bg-slate-50 transition-colors">
                <span class="text-[10px] font-bold text-slate-400 uppercase">C3: Perputaran (Benefit)</span>
                <span class="text-lg font-black text-indigo-600 mt-1">{{ number_format(($this->spkData['weights']['c3_perputaran'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5">Entropy: {{ number_format($this->spkData['entropy']['c3_perputaran'] ?? 0, 4) }}</span>
            </div>
            <div class="bg-slate-50/50 p-3.5 rounded-2xl border border-slate-100 flex flex-col justify-between hover:bg-slate-50 transition-colors">
                <span class="text-[10px] font-bold text-slate-400 uppercase">C4: Lead Time (Cost)</span>
                <span class="text-lg font-black text-indigo-600 mt-1">{{ number_format(($this->spkData['weights']['c4_lead_time'] ?? 0) * 100, 1) }}%</span>
                <span class="text-[9px] text-slate-400 mt-0.5">Entropy: {{ number_format($this->spkData['entropy']['c4_lead_time'] ?? 0, 4) }}</span>
            </div>
        </div>
    </div>

    {{-- ── Recommendation Subtitle ── --}}
    <div class="flex items-center justify-between border-b border-slate-100 pb-3 mt-4">
        <h2 class="text-sm font-black text-slate-800 uppercase tracking-wider flex items-center gap-2">
            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
            Daftar Rekomendasi Urutan Restock
        </h2>
        <span class="text-xs text-slate-400 font-semibold">{{ count($this->spkData['results']) }} Produk Dianalisis</span>
    </div>

    {{-- ── Daftar Produk Card List ── --}}
    <div class="space-y-4">
        @foreach($this->spkData['results'] as $item)
        @php
            $isKritis    = $item['priority'] === 'kritis';
            $isPerhatian = $item['priority'] === 'perhatian';
            $hasBuy      = $item['recommended_buy'] > 0;

            // Accent gradient borders
            $accentColor = $isKritis 
                ? 'bg-gradient-to-b from-red-500 to-rose-600' 
                : ($isPerhatian ? 'bg-gradient-to-b from-amber-400 to-yellow-500' : 'bg-gradient-to-b from-emerald-400 to-teal-500');
            
            $badgeCls    = $isKritis
                ? 'bg-rose-50 text-rose-700 border border-rose-100'
                : ($isPerhatian ? 'bg-amber-50 text-amber-700 border border-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-100');
            
            // Premium circular rank badges
            $rankCls = 'bg-slate-100 text-slate-500 border border-slate-200';
            if ($item['rank'] == 1) {
                $rankCls = 'bg-gradient-to-br from-amber-300 via-yellow-400 to-amber-500 text-white border border-amber-300 shadow-sm font-extrabold';
            } elseif ($item['rank'] == 2) {
                $rankCls = 'bg-gradient-to-br from-slate-200 via-slate-300 to-slate-400 text-slate-800 border border-slate-200 shadow-sm font-bold';
            } elseif ($item['rank'] == 3) {
                $rankCls = 'bg-gradient-to-br from-amber-600 via-amber-700 to-amber-800 text-white border border-amber-600 shadow-sm font-bold';
            }

            $buyColor    = $isKritis ? 'text-rose-600' : 'text-amber-600';
            $buyBadge    = $isKritis ? 'bg-rose-100' : 'bg-amber-100';
        @endphp

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm relative overflow-hidden transition-all duration-300 hover:-translate-y-1 hover:shadow-lg hover:border-indigo-100"
             style="box-shadow: 0 4px 20px rgba(0,0,0,0.02), 0 1px 3px rgba(0,0,0,0.01);">

            {{-- Left Accent Line --}}
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $accentColor }}"></div>

            <div class="p-5 pl-7 space-y-4">
                {{-- Top Row: Info & Recommendation Qty --}}
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5 min-w-0">
                        {{-- Rank --}}
                        <span class="mt-0.5 flex-shrink-0 w-7 h-7 rounded-full flex items-center justify-center text-xs shadow-inner {{ $rankCls }}">
                            {{ $item['rank'] }}
                        </span>
                        <div class="min-w-0">
                            <h3 class="font-extrabold text-slate-800 text-sm sm:text-base truncate hover:text-indigo-600 transition-colors">{{ $item['name'] }}</h3>
                            <div class="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                <span class="text-xs text-slate-500 font-semibold bg-slate-100 px-2 py-0.5 rounded-md">{{ $item['category'] }}</span>
                                @if($item['sku'])
                                    <span class="text-[10px] font-mono text-slate-400 bg-slate-50 px-1.5 py-0.5 border border-slate-100 rounded-md">SKU: {{ $item['sku'] }}</span>
                                @endif
                                <span class="text-[10px] font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 border border-indigo-100 rounded-md">Skor SAW: {{ number_format($item['score'], 4) }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Recommendation Output Box (High Contrast) --}}
                    <div class="text-right flex-shrink-0">
                        @if($hasBuy)
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl {{ $buyBadge }} {{ $buyColor }} font-black text-sm sm:text-base shadow-sm">
                                ➕ {{ number_format($item['recommended_buy'], 0) }}
                                <span class="text-[10px] uppercase font-bold opacity-80">{{ $item['unit'] }}</span>
                            </div>
                            <p class="text-[9px] uppercase tracking-wider text-slate-400 font-bold mt-1">Rekomendasi Beli</p>
                        @else
                            <div class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-600 font-extrabold text-xs sm:text-sm border border-emerald-100">
                                ✔️ Stok Cukup
                            </div>
                            <p class="text-[9px] uppercase tracking-wider text-slate-400 font-bold mt-1">Tidak Perlu Beli</p>
                        @endif
                    </div>
                </div>

                {{-- Middle Grid: Metrics & Statistics --}}
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 pt-3 border-t border-slate-50">
                    {{-- Sisa Stok (C1) --}}
                    <div class="bg-slate-50/50 rounded-xl p-2.5 text-center border border-slate-100/50 hover:bg-slate-50 transition-colors flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Sisa Stok (C1)</p>
                        <p class="text-sm sm:text-base font-black text-slate-700 mt-1">
                            {{ number_format($item['c1_stok'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">{{ $item['unit'] }}</span>
                        </p>
                        <p class="text-[9px] font-bold mt-1 {{ $isKritis ? 'text-rose-600' : ($isPerhatian ? 'text-amber-600' : 'text-emerald-600') }}">
                            {{ $item['sisa_hari'] == 999 ? '⏳ > 30 hari' : '⏳ ~' . $item['sisa_hari'] . ' hari' }}
                        </p>
                    </div>

                    {{-- Terjual (C2) --}}
                    <div class="bg-slate-50/50 rounded-xl p-2.5 text-center border border-slate-100/50 hover:bg-slate-50 transition-colors flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Terjual (C2)</p>
                        <p class="text-sm sm:text-base font-black text-slate-700 mt-1">
                            {{ number_format($item['c2_terjual'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">{{ $item['unit'] }}</span>
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Laju: {{ $item['daily_rate'] > 0 ? number_format($item['daily_rate'], 1) : '0' }}/hr</p>
                    </div>

                    {{-- Perputaran Stok (C3) --}}
                    <div class="bg-slate-50/50 rounded-xl p-2.5 text-center border border-slate-100/50 hover:bg-slate-50 transition-colors flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Perputaran (C3)</p>
                        <p class="text-sm sm:text-base font-black text-slate-700 mt-1">
                            {{ number_format($item['c3_perputaran'], 2) }}
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Rasio C2/C1</p>
                    </div>

                    {{-- Lead Time Supplier (C4) --}}
                    <div class="bg-slate-50/50 rounded-xl p-2.5 text-center border border-slate-100/50 hover:bg-slate-50 transition-colors flex flex-col justify-between">
                        <p class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Lead Time (C4)</p>
                        <p class="text-sm sm:text-base font-black text-slate-700 mt-1">
                            {{ number_format($item['c4_lead_time'], 0) }}
                            <span class="text-xs font-semibold text-slate-400">hari</span>
                        </p>
                        <p class="text-[9px] font-bold text-slate-400 mt-1">Waktu Kirim</p>
                    </div>

                    {{-- Status Priority Badge --}}
                    <div class="flex items-center justify-center p-2.5 col-span-2 sm:col-span-1">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold shadow-sm {{ $badgeCls }}">
                            {{ $isKritis ? '🔴 Kritis' : ($isPerhatian ? '🟡 Perhatian' : '🟢 Aman') }}
                        </span>
                    </div>
                </div>

                {{-- Bottom Recommendation Banner (Only when recommended_buy > 0) --}}
                @if($hasBuy)
                <div class="rounded-xl border border-dashed p-3 flex items-center gap-2.5 {{ $isKritis ? 'border-rose-200 bg-rose-50/30 text-rose-800' : 'border-amber-200 bg-amber-50/20 text-amber-800' }}">
                    <span class="text-sm shrink-0">💡</span>
                    <p class="text-[11px] sm:text-xs leading-relaxed font-semibold">
                        Saran pengadaan: <span class="font-bold underline">Beli {{ number_format($item['recommended_buy'], 0) }} {{ $item['unit'] }}</span>
                        untuk menjaga ketersediaan hingga <span class="font-bold">{{ $targetDays }} hari</span> ke depan 
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

    {{-- ── Footer Analytics Info ── --}}
    <div class="flex flex-col items-center justify-center gap-1 text-[10px] text-slate-400 font-bold uppercase tracking-wider pt-4 text-center">
        <div>Rumus Proyeksi Beli: <span class="text-slate-500">(Laju Harian × Target Hari) − Sisa Stok</span></div>
        <div class="mt-1">Normalisasi SAW: <span class="text-slate-500">C1, C4 (Cost: min/x), C2-C3 (Benefit: x/max)</span></div>
    </div>

    @endif

</div>

