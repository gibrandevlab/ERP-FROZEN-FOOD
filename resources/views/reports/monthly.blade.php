<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan - {{ $month_label }}</title>
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

        .badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 9px; font-weight: 700; }
        .badge-green { background: #d1fae5; color: #065f46; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-amber { background: #fef3c7; color: #92400e; }

        .summary-box { border: 1px solid #bae6fd; border-radius: 8px; padding: 16px; margin-top: 20px; background: #f0f9ff; }
        .summary-row { display: table; width: 100%; }
        .summary-cell-label { display: table-cell; width: 65%; font-weight: 600; color: #475569; padding: 4px 0; font-size: 11px; }
        .summary-cell-value { display: table-cell; width: 35%; text-align: right; font-weight: 800; padding: 4px 0; font-size: 11px; }
        .summary-divider { border-top: 1px dashed #cbd5e1; margin: 8px 0; }

        .profit-positive { color: #059669; }
        .profit-negative { color: #dc2626; }

        .footer { text-align: center; padding: 20px 32px; color: #94a3b8; font-size: 9px; border-top: 1px solid #e2e8f0; margin-top: 20px; }

        .page-break { page-break-before: always; }
    </style>
</head>
<body>
    {{-- ── Header ──────────────────────────────────────────────────── --}}
    <div class="header">
        <h1>📊 RIZA FROZEN FOOD</h1>
        <div class="subtitle">Laporan Keuangan Bulanan</div>
        <div class="date-badge">Periode: {{ $month_label }}</div>
    </div>

    <div class="content">
        {{-- ── KPI Ringkasan ──────────────────────────────────────────── --}}
        <div class="kpi-grid">
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Total Pemasukan</div>
                        <div class="kpi-value kpi-income">Rp {{ number_format($total_income, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Total Pengeluaran</div>
                        <div class="kpi-value kpi-expense">Rp {{ number_format($total_expense, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Total Piutang</div>
                        <div class="kpi-value kpi-receivable">Rp {{ number_format($total_receivable, 0, ',', '.') }}</div>
                    </div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-inner">
                        <div class="kpi-label">Total Utang</div>
                        <div class="kpi-value kpi-payable">Rp {{ number_format($total_payable, 0, ',', '.') }}</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Ringkasan Keuangan ─────────────────────────────────────── --}}
        <div class="summary-box">
            <div class="summary-row">
                <div class="summary-cell-label">Pendapatan Bersih (Lunas)</div>
                <div class="summary-cell-value kpi-income">Rp {{ number_format($total_income, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell-label">Pengeluaran Bersih (Lunas)</div>
                <div class="summary-cell-value kpi-expense">Rp {{ number_format($total_expense, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell-label">&nbsp;&nbsp;&nbsp;↳ Pembelian Stok (Restock)</div>
                <div class="summary-cell-value" style="color:#64748b;">Rp {{ number_format($total_expense - $total_operasional, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell-label">&nbsp;&nbsp;&nbsp;↳ Biaya Operasional</div>
                <div class="summary-cell-value" style="color:#64748b;">Rp {{ number_format($total_operasional, 0, ',', '.') }}</div>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row">
                <div class="summary-cell-label" style="font-size:13px; font-weight:800;">LABA / RUGI BERSIH</div>
                <div class="summary-cell-value {{ $laba >= 0 ? 'profit-positive' : 'profit-negative' }}" style="font-size:15px;">
                    {{ $laba >= 0 ? '' : '-' }}Rp {{ number_format(abs($laba), 0, ',', '.') }}
                </div>
            </div>
            <div class="summary-divider"></div>
            <div class="summary-row">
                <div class="summary-cell-label">Total Piutang Belum Terbayar</div>
                <div class="summary-cell-value kpi-receivable">Rp {{ number_format($total_receivable, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell-label">Total Utang Belum Terbayar</div>
                <div class="summary-cell-value kpi-payable">Rp {{ number_format($total_payable, 0, ',', '.') }}</div>
            </div>
            <div class="summary-row">
                <div class="summary-cell-label">Jumlah Transaksi</div>
                <div class="summary-cell-value" style="color:#0f172a;">{{ $total_transactions }} transaksi</div>
            </div>
        </div>

        {{-- ── Top Produk Terjual ──────────────────────────────────────── --}}
        @if(count($sales_by_product) > 0)
        <div class="section">
            <div class="section-title">Penjualan Per Produk (Top Seller)</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:50%;">Nama Produk</th>
                        <th style="width:15%;" class="text-center">Qty Terjual</th>
                        <th style="width:30%;" class="text-right">Total Penjualan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales_by_product as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="font-bold">{{ $item['product_name'] }}</td>
                        <td class="text-center">{{ $item['qty'] }}</td>
                        <td class="text-right font-bold text-green">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Pembelian Per Supplier ──────────────────────────────────── --}}
        @if(count($purchase_by_supplier) > 0)
        <div class="section">
            <div class="section-title">Pembelian Per Supplier</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:45%;">Nama Supplier</th>
                        <th style="width:15%;" class="text-center">Jml Transaksi</th>
                        <th style="width:35%;" class="text-right">Total Pembelian</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchase_by_supplier as $i => $item)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td class="font-bold">{{ $item['supplier_name'] }}</td>
                        <td class="text-center">{{ $item['count'] }}×</td>
                        <td class="text-right font-bold text-red">Rp {{ number_format($item['total'], 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Daftar Piutang ──────────────────────────────────────────── --}}
        @if($receivables->count() > 0)
        <div class="section">
            <div class="section-title">Daftar Piutang (Pelanggan Ngutang)</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:25%;">Pelanggan</th>
                        <th style="width:25%;">Produk</th>
                        <th style="width:15%;" class="text-center">Tgl Transaksi</th>
                        <th style="width:15%;" class="text-center">Jatuh Tempo</th>
                        <th style="width:15%;" class="text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receivables as $i => $l)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="font-bold">{{ $l->customer?->name ?? 'Umum' }}</td>
                        <td>{{ $l->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ $l->date->format('d/m') }}</td>
                        <td class="text-center text-amber font-bold">{{ $l->due_date ? $l->due_date->format('d/m') : '-' }}</td>
                        <td class="text-right font-bold text-amber">Rp {{ number_format($l->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- ── Daftar Utang ────────────────────────────────────────────── --}}
        @if($payables->count() > 0)
        <div class="section">
            <div class="section-title">Daftar Utang (Ke Supplier)</div>
            <table class="data">
                <thead>
                    <tr>
                        <th style="width:5%;">#</th>
                        <th style="width:25%;">Supplier</th>
                        <th style="width:25%;">Produk</th>
                        <th style="width:15%;" class="text-center">Tgl Transaksi</th>
                        <th style="width:15%;" class="text-center">Jatuh Tempo</th>
                        <th style="width:15%;" class="text-right">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payables as $i => $l)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td class="font-bold">{{ $l->supplier?->name ?? '-' }}</td>
                        <td>{{ $l->product?->name ?? '-' }}</td>
                        <td class="text-center">{{ $l->date->format('d/m') }}</td>
                        <td class="text-center text-red font-bold">{{ $l->due_date ? $l->due_date->format('d/m') : '-' }}</td>
                        <td class="text-right font-bold text-red">Rp {{ number_format($l->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="footer">
        Laporan ini digenerate secara otomatis oleh Sistem ERP Riza Frozen Food pada {{ now('Asia/Jakarta')->translatedFormat('d F Y, H:i') }} WIB.
    </div>
</body>
</html>
