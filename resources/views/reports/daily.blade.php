<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Harian - {{ $date->format('d/m/Y') }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica', 'Arial', sans-serif; font-size: 11px; color: #1e293b; line-height: 1.5; }

        .header { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); color: white; padding: 28px 32px; }
        .header h1 { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .header .subtitle { font-size: 11px; color: #94a3b8; margin-top: 4px; }
        .header .date-badge { background: rgba(255,255,255,0.15); border-radius: 6px; padding: 6px 14px; display: inline-block; margin-top: 10px; font-size: 12px; font-weight: 700; }

        .content { padding: 24px 32px; }

        .kpi-grid { display: table; width: 100%; margin-bottom: 20px; }
        .kpi-row { display: table-row; }
        .kpi-card { display: table-cell; width: 25%; padding: 4px; }
        .kpi-inner { border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px 16px; background: #f8fafc; }
        .kpi-label { font-size: 9px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px; }
        .kpi-value { font-size: 16px; font-weight: 800; margin-top: 4px; }
        .kpi-income { color: #059669; }
        .kpi-expense { color: #dc2626; }
        .kpi-receivable { color: #d97706; }
        .kpi-payable { color: #7c3aed; }

        .section { margin-top: 24px; }
        .section-title { font-size: 13px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 12px; }

        table.data { width: 100%; border-collapse: collapse; font-size: 10px; }
        table.data thead { background: #f1f5f9; }
        table.data th { text-align: left; padding: 8px 10px; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 9px; letter-spacing: 0.3px; border-bottom: 2px solid #e2e8f0; }
        table.data td { padding: 7px 10px; border-bottom: 1px solid #f1f5f9; }
        table.data tr:nth-child(even) { background: #fafbfc; }
        table.data .text-right { text-align: right; }
        table.data .text-center { text-align: center; }
        table.data .font-bold { font-weight: 700; }
        table.data .text-green { color: #059669; }
        table.data .text-red { color: #dc2626; }
        table.data .text-amber { color: #d97706; }
        table.data .text-purple { color: #7c3aed; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-amber { background: #fef3c7; color: #92400e; }

        .summary-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; margin-top: 20px; }
        .summary-row { display: table; width: 100%; }
        .summary-label { display: table-cell; width: 70%; font-weight: 600; color: #475569; padding: 3px 0; }
        .summary-value { display: table-cell; width: 30%; text-align: right; font-weight: 800; padding: 3px 0; }

        .footer { text-align: center; padding: 20px 32px; color: #94a3b8; font-size: 9px; border-top: 1px solid #e2e8f0; margin-top: 20px; }

        .profit-positive { color: #059669; }
        .profit-negative { color: #dc2626; }

        .empty-state { text-align: center; padding: 30px; color: #94a3b8; font-size: 12px; }
    </style>
</head>
<body>
    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>📊 RIZA FROZEN FOOD</h1>
        <div class="subtitle">Laporan Keuangan Harian</div>
        <div class="date-badge">{{ $date->translatedFormat('l, d F Y') }}</div>
    </div>

    <div class="content">
        {{-- ── KPI Ringkasan ──────────────────────────────────────────── --}}
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Pemasukan (Lunas)</div>
                        <div class="kpi-value kpi-income">Rp {{ number_format($total_income, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Pengeluaran (Lunas)</div>
                        <div class="kpi-value kpi-expense">Rp {{ number_format($total_expense, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Piutang (Belum Lunas)</div>
                        <div class="kpi-value kpi-receivable">Rp {{ number_format($total_receivable, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Utang (Belum Lunas)</div>
                        <div class="kpi-value kpi-payable">Rp {{ number_format($total_payable, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Laba/Rugi ──────────────────────────────────────────────── --}}
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-label">Laba/Rugi Bersih Hari Ini (Lunas)</div>
                <div class="summary-value {{ $laba >= 0 ? 'profit-positive' : 'profit-negative' }}">
                    {{ $laba >= 0 ? '' : '-' }}Rp {{ number_format(abs($laba), 0, ',', '.') }}
                </div>
            </div>
            <div class="summary-row">
                <div class="summary-label">Total Transaksi</div>
                <div class="summary-value">{{ $total_transactions }} transaksi</div>
            </div>
        </div>

        {{-- ── Daftar Transaksi ───────────────────────────────────────── --}}
        @if($ledgers->count() > 0)
        <div class="section">
            <div class="section-title">Rincian Seluruh Transaksi</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 28%;">Judul</th>
                        <th style="width: 15%;">Produk</th>
                        <th style="width: 12%;">Pelanggan/Supplier</th>
                        <th style="width: 10%;" class="text-center">Status</th>
                        <th style="width: 8%;" class="text-center">Qty</th>
                        <th style="width: 17%;" class="text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($ledgers as $i => $l)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="font-bold">{{ $l->title }}</td>
                        <td>{{ $l->product?->name ?? '-' }}</td>
                        <td>{{ $l->customer?->name ?? $l->supplier?->name ?? '-' }}</td>
                        <td class="text-center">
                            @if($l->payment_status === 'paid')
                                <span class="badge badge-green">Lunas</span>
                            @else
                                <span class="badge badge-amber">Utang</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $l->quantity ?? '-' }}</td>
                        <td class="text-right font-bold {{ $l->type === 'income' ? 'text-green' : 'text-red' }}">
                            {{ $l->type === 'income' ? '+' : '-' }}Rp {{ number_format($l->amount, 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="empty-state">Tidak ada transaksi pada tanggal ini.</div>
        @endif
    </div>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh Sistem ERP Riza Frozen Food pada {{ now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB.
    </div>
</body>
</html>
