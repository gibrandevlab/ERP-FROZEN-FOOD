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
        $this->authorize('view-ledger');
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

<div class="space-y-5 max-w-4xl mx-auto lg:max-w-none">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-2">
        <div class="flex items-center gap-3">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-500 hover:text-slate-700 hover:bg-slate-50 shadow-sm transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-xl font-extrabold" style="color: #1E293B;">Ringkasan Pembukuan</h1>
                <p class="text-xs text-slate-500 mt-0.5">Laporan arus kas bulanan</p>
            </div>
        </div>
        <select wire:model.live="tahun" @change="playClick()"
                class="px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/30 focus:border-blue-400 shadow-sm transition-all cursor-pointer">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}">Tahun {{ $y }}</option>
            @endfor
        </select>
    </div>

    {{-- Total Tahunan --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Pemasukan</p>
            <p class="text-2xl font-extrabold text-emerald-600">Rp {{ $totalIncome }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 p-5 shadow-sm">
            <p class="text-xs text-slate-500 uppercase tracking-wider font-semibold mb-2">Total Pengeluaran</p>
            <p class="text-2xl font-extrabold text-red-500">Rp {{ $totalExpense }}</p>
        </div>
        <div class="bg-white rounded-2xl border p-5 shadow-sm {{ $labaPositif ? 'border-blue-100 bg-blue-50/30' : 'border-red-100 bg-red-50/30' }}">
            <p class="text-xs uppercase tracking-wider font-semibold mb-2 {{ $labaPositif ? 'text-blue-500' : 'text-red-500' }}">Laba Kotor</p>
            <p class="text-2xl font-extrabold {{ $labaPositif ? 'text-blue-600' : 'text-red-600' }}">
                {{ $labaPositif ? '' : '-' }}Rp {{ $totalLaba }}
            </p>
        </div>
    </div>

    {{-- Tabel Per Bulan --}}
    <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden" style="box-shadow: 0 4px 40px rgba(0,0,0,0.05), 0 1px 8px rgba(0,0,0,0.04);">
        <table class="w-full text-sm">
            <thead style="background: linear-gradient(135deg, rgba(248,250,252,0.95), rgba(241,245,249,0.95));">
                <tr class="border-b border-slate-100">
                    <th class="px-5 py-3.5 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Bulan</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Pemasukan</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Pengeluaran</th>
                    <th class="px-5 py-3.5 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Laba Bersih</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-50">
                @foreach($dataPerBulan as $row)
                <tr class="{{ $row['income'] == 0 && $row['expense'] == 0 ? 'bg-slate-50/30 text-slate-400' : 'hover:bg-blue-50/30 transition-colors text-slate-800' }}">
                    <td class="px-5 py-4 font-semibold">
                        {{ $row['bulan'] }}
                    </td>
                    <td class="px-5 py-4 text-right font-medium {{ $row['income'] > 0 ? 'text-emerald-600' : '' }}">
                        {{ $row['income'] > 0 ? 'Rp '.number_format($row['income'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-5 py-4 text-right font-medium {{ $row['expense'] > 0 ? 'text-red-500' : '' }}">
                        {{ $row['expense'] > 0 ? 'Rp '.number_format($row['expense'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-5 py-4 text-right font-bold {{ $row['laba'] > 0 ? 'text-blue-600' : ($row['laba'] < 0 ? 'text-red-600' : '') }}">
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
