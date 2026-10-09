<?php

use App\Models\Ledger;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component {

    public string $tahun = '';

    // Data per bulan [ ['bulan'=>'Januari', 'income'=>..., 'expense'=>..., 'laba'=>...] ]
    public array $dataPerBulan = [];

    public string $totalIncome  = '0';
    public string $totalExpense = '0';
    public string $totalLaba    = '0';
    public bool   $labaPositif  = true;

    public function mount(): void
    {
        $this->authorize('view-ringkasan');
        $this->tahun = now()->format('Y');
        $this->hitungRingkasan();
    }

    public function updatedTahun(): void
    {
        $this->hitungRingkasan();
    }

    private function hitungRingkasan(): void
    {
        $namaBulan = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

        $this->dataPerBulan = [];
        $grandIncome  = 0;
        $grandExpense = 0;

        for ($m = 1; $m <= 12; $m++) {
            $bulanStr = sprintf('%s-%02d', $this->tahun, $m);

            $income  = Ledger::income()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanStr])->sum('amount');
            $expense = Ledger::expense()->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$bulanStr])->sum('amount');
            $laba    = $income - $expense;

            $grandIncome  += $income;
            $grandExpense += $expense;

            $this->dataPerBulan[] = [
                'no'      => $m,
                'bulan'   => $namaBulan[$m - 1],
                'income'  => $income,
                'expense' => $expense,
                'laba'    => $laba,
            ];
        }

        $grandLaba            = $grandIncome - $grandExpense;
        $this->labaPositif    = $grandLaba >= 0;
        $this->totalIncome    = number_format($grandIncome, 0, ',', '.');
        $this->totalExpense   = number_format($grandExpense, 0, ',', '.');
        $this->totalLaba      = number_format(abs($grandLaba), 0, ',', '.');
    }
}; ?>

<div class="space-y-6 max-w-5xl mx-auto">

    {{-- ── Banner Header ── --}}
    <div class="relative overflow-hidden rounded-3xl border border-blue-100/80 bg-gradient-to-r from-blue-50 via-white to-sky-50 p-5 sm:p-6 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-2xl bg-white border border-slate-200/80 text-slate-600 hover:text-blue-600 hover:border-blue-200 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-blue-100/80 text-blue-700 rounded-lg">Laporan Tahunan</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-slate-900 mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Ringkasan Pembukuan
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-600 mt-0.5">Rekapitulasi arus kas masuk, keluar, dan kalkulasi laba rugi bulanan.</p>
            </div>
        </div>

        <select wire:model.live="tahun" @change="playClick()"
                class="px-4 py-2.5 bg-white border border-slate-200/80 rounded-2xl text-xs sm:text-sm font-bold text-slate-800 focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-sm transition-all cursor-pointer self-start sm:self-auto shrink-0">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}">Tahun {{ $y }}</option>
            @endfor
        </select>
    </div>

    {{-- ── Total Tahunan KPI ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Pemasukan</span>
            <p class="text-2xl sm:text-3xl font-extrabold text-emerald-600">Rp {{ $totalIncome }}</p>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/80 p-5 shadow-sm">
            <span class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total Pengeluaran</span>
            <p class="text-2xl sm:text-3xl font-extrabold text-rose-600">Rp {{ $totalExpense }}</p>
        </div>

        <div class="bg-white rounded-3xl border p-5 shadow-sm {{ $labaPositif ? 'border-blue-200/80 bg-gradient-to-br from-blue-50/50 to-white' : 'border-rose-200/80 bg-gradient-to-br from-rose-50/50 to-white' }}">
            <span class="block text-xs font-bold uppercase tracking-wider mb-1 {{ $labaPositif ? 'text-blue-600' : 'text-rose-600' }}">Laba Kotor / Bersih</span>
            <p class="text-2xl sm:text-3xl font-extrabold {{ $labaPositif ? 'text-blue-600' : 'text-rose-600' }}">
                {{ $labaPositif ? '' : '-' }}Rp {{ $totalLaba }}
            </p>
        </div>
    </div>

    {{-- ── Tabel Per Bulan ── --}}
    <div class="bg-white rounded-3xl border border-slate-200/80 overflow-hidden shadow-sm">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200/80 text-[11px] font-extrabold text-slate-500 uppercase tracking-wider">
                    <th class="px-6 py-4">Bulan</th>
                    <th class="px-6 py-4 text-right">Pemasukan</th>
                    <th class="px-6 py-4 text-right">Pengeluaran</th>
                    <th class="px-6 py-4 text-right">Laba Bersih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs sm:text-sm font-medium text-slate-700">
                @foreach($dataPerBulan as $row)
                <tr class="{{ $row['income'] == 0 && $row['expense'] == 0 ? 'bg-slate-50/30 text-slate-400' : 'hover:bg-blue-50/30 transition-colors text-slate-800' }}">
                    <td class="px-6 py-4 font-extrabold">
                        {{ $row['bulan'] }}
                    </td>
                    <td class="px-6 py-4 text-right font-bold {{ $row['income'] > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $row['income'] > 0 ? 'Rp '.number_format($row['income'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right font-bold {{ $row['expense'] > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                        {{ $row['expense'] > 0 ? 'Rp '.number_format($row['expense'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $row['laba'] > 0 ? 'text-blue-600' : ($row['laba'] < 0 ? 'text-rose-600' : 'text-slate-400') }}">
                        @if($row['income'] == 0 && $row['expense'] == 0) —
                        @elseif($row['laba'] >= 0) Rp {{ number_format($row['laba'], 0, ',', '.') }}
                        @else -Rp {{ number_format(abs($row['laba']), 0, ',', '.') }}
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
