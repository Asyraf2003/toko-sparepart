<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Pembelian</title>
    <style>
        @page { margin: 18mm 14mm; }
        body { font-family: DejaVu Sans, system-ui, -apple-system, Segoe UI, Roboto, sans-serif; font-size: 12px; color: #111827; }
        h1 { font-size: 18px; margin: 0 0 6px; }
        h2 { font-size: 14px; margin: 18px 0 8px; }
        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 10px; margin-bottom: 12px; }
        .meta { width: 100%; border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin: 10px 0 14px; }
        .meta-grid { width: 100%; border-collapse: collapse; }
        .meta-grid td { padding: 3px 0; vertical-align: top; }
        .meta-grid td:first-child { width: 140px; color: #6b7280; }
        .summary-grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .summary-grid td { padding: 6px 8px; border: 1px solid #e5e7eb; }
        .summary-grid td.label { width: 55%; color: #374151; }
        .summary-grid td.val { text-align: right; font-weight: 600; }
        .summary-grid tr.total td { background: #f3f4f6; font-weight: 700; }
        table.report { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.report th, table.report td { border: 1px solid #e5e7eb; padding: 6px 8px; }
        table.report th { background: #f3f4f6; text-align: left; font-weight: 700; }
        td.num, th.num { text-align: right; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f3f4f6; font-size: 11px; }
        .footer { margin-top: 14px; border-top: 1px solid #e5e7eb; padding-top: 8px; font-size: 11px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<div class="header">
    <h1>Laporan Pembelian</h1>
    <div class="muted">APP KASIR · Laporan Pembelian Supplier</div>
</div>

<div class="meta">
    <table class="meta-grid">
        <tr><td>Dibuat pada</td><td>: <b>{{ $generated_at }}</b></td></tr>
        <tr><td>Periode</td><td>: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></td></tr>
        <tr><td>No Faktur</td><td>: <span class="badge">{{ $filters['no_faktur'] ?? '(semua)' }}</span></td></tr>
    </table>
</div>

<h2>Ringkasan</h2>
<table class="summary-grid">
    <tr><td class="label">Jumlah</td><td class="val">{{ $result->summary->count }}</td></tr>
    <tr><td class="label">Total Bruto</td><td class="val">{{ $fmt($result->summary->totalBruto) }}</td></tr>
    <tr><td class="label">Total Diskon</td><td class="val">{{ $fmt($result->summary->totalDiskon) }}</td></tr>
    <tr><td class="label">Total Pajak</td><td class="val">{{ $fmt($result->summary->totalPajak) }}</td></tr>
    <tr class="total"><td class="label">Total Akhir</td><td class="val">{{ $fmt($result->summary->grandTotal) }}</td></tr>
</table>

<h2>Detail</h2>
<table class="report">
    <thead>
    <tr>
        <th style="width: 95px;">Tgl Kirim</th>
        <th style="width: 130px;">No Faktur</th>
        <th>Supplier</th>
        <th class="num">Total Bruto</th>
        <th class="num">Total Diskon</th>
        <th class="num">Total Pajak</th>
        <th class="num">Total Akhir</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td>{{ $r->tglKirim }}</td>
            <td><b>{{ $r->noFaktur }}</b></td>
            <td>{{ $r->supplierName }}</td>
            <td class="num">{{ $fmt($r->totalBruto) }}</td>
            <td class="num">{{ $fmt($r->totalDiskon) }}</td>
            <td class="num">{{ $fmt($r->totalPajak) }}</td>
            <td class="num"><b>{{ $fmt($r->grandTotal) }}</b></td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">
    Dicetak dari APP KASIR · Laporan Pembelian
</div>

</body>
</html>