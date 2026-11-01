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
    <div class="relative overflow-hidden rounded-lg border border-[#dce1de]/80 bg-gradient-to-r from-[#f5f6f3] via-white to-[#f5f6f3] p-5 sm:p-6 shadow-none flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div class="flex items-center gap-3.5">
            <a href="{{ route('pembukuan.index') }}" wire:navigate @click="playClick()"
               class="btn-sound w-10 h-10 sm:w-11 sm:h-11 flex items-center justify-center rounded-lg bg-white border border-[#dce1de]/80 text-[#52646a] hover:text-[#315b50] hover:border-[#dce1de] shadow-none transition-all shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <span class="inline-flex items-center px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-[#e8efeb]/80 text-[#17343d] rounded-lg">Laporan Tahunan</span>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight text-[#172c35] mt-1 flex items-center gap-2">
                    <svg class="w-6 h-6 text-[#315b50] shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Ringkasan Pembukuan
                </h1>
                <p class="text-xs sm:text-sm font-medium text-[#52646a] mt-0.5">Rekapitulasi arus kas masuk, keluar, dan kalkulasi laba rugi bulanan.</p>
            </div>
        </div>

        <select wire:model.live="tahun" @change="playClick()"
                class="px-4 py-2.5 bg-white border border-[#dce1de]/80 rounded-lg text-xs sm:text-sm font-bold text-[#344950] focus:ring-2 focus:ring-[#527c70]/20 focus:border-[#527c70] shadow-none transition-all cursor-pointer self-start sm:self-auto shrink-0">
            @for($y = now()->year; $y >= now()->year - 5; $y--)
                <option value="{{ $y }}">Tahun {{ $y }}</option>
            @endfor
        </select>
    </div>

    {{-- ── Total Tahunan KPI ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none">
            <span class="block text-xs font-bold text-[#71817f] uppercase tracking-wider mb-1">Total Pemasukan</span>
            <p class="text-2xl sm:text-3xl font-extrabold text-[#2f6b5c]">Rp {{ $totalIncome }}</p>
        </div>

        <div class="bg-white rounded-lg border border-[#dce1de]/80 p-5 shadow-none">
            <span class="block text-xs font-bold text-[#71817f] uppercase tracking-wider mb-1">Total Pengeluaran</span>
            <p class="text-2xl sm:text-3xl font-extrabold text-[#9c5252]">Rp {{ $totalExpense }}</p>
        </div>

        <div class="bg-white rounded-lg border p-5 shadow-none {{ $labaPositif ? 'border-[#dce1de]/80 bg-gradient-to-br from-[#f5f6f3]/50 to-white' : 'border-[#ead6d4]/80 bg-gradient-to-br from-[#f7eceb]/50 to-white' }}">
            <span class="block text-xs font-bold uppercase tracking-wider mb-1 {{ $labaPositif ? 'text-[#315b50]' : 'text-[#9c5252]' }}">Laba Kotor / Bersih</span>
            <p class="text-2xl sm:text-3xl font-extrabold {{ $labaPositif ? 'text-[#315b50]' : 'text-[#9c5252]' }}">
                {{ $labaPositif ? '' : '-' }}Rp {{ $totalLaba }}
            </p>
        </div>
    </div>

    {{-- ── Tabel Per Bulan ── --}}
    <div class="bg-white rounded-lg border border-[#dce1de]/80 overflow-hidden shadow-none">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-[#f5f6f3]/80 border-b border-[#dce1de]/80 text-[11px] font-extrabold text-[#64747a] uppercase tracking-wider">
                    <th class="px-6 py-4">Bulan</th>
                    <th class="px-6 py-4 text-right">Pemasukan</th>
                    <th class="px-6 py-4 text-right">Pengeluaran</th>
                    <th class="px-6 py-4 text-right">Laba Bersih</th>
                </tr>
            </thead>
            <tbody class="divide-y border-[#e6eae7] text-xs sm:text-sm font-medium text-[#344950]">
                @foreach($dataPerBulan as $row)
                <tr class="{{ $row['income'] == 0 && $row['expense'] == 0 ? 'bg-[#f5f6f3]/30 text-[#71817f]' : 'hover:bg-[#f5f6f3]/30 transition-colors text-[#344950]' }}">
                    <td class="px-6 py-4 font-extrabold">
                        {{ $row['bulan'] }}
                    </td>
                    <td class="px-6 py-4 text-right font-bold {{ $row['income'] > 0 ? 'text-[#2f6b5c]' : 'text-[#71817f]' }}">
                        {{ $row['income'] > 0 ? 'Rp '.number_format($row['income'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right font-bold {{ $row['expense'] > 0 ? 'text-[#9c5252]' : 'text-[#71817f]' }}">
                        {{ $row['expense'] > 0 ? 'Rp '.number_format($row['expense'], 0, ',', '.') : '—' }}
                    </td>
                    <td class="px-6 py-4 text-right font-extrabold {{ $row['laba'] > 0 ? 'text-[#315b50]' : ($row['laba'] < 0 ? 'text-[#9c5252]' : 'text-[#71817f]') }}">
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
