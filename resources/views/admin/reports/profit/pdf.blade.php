<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Laba</title>
    <style>
        @page { size: A4 landscape; margin: 14mm 12mm; }

        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            font-size: 10px;
            color: #111827;
        }

        h1 { font-size: 16px; margin: 0 0 6px; }
        h2 { font-size: 12px; margin: 14px 0 6px; }

        .muted { color: #6b7280; }
        .header { border-bottom: 2px solid #111827; padding-bottom: 8px; margin-bottom: 10px; }

        .meta {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 8px;
            margin: 8px 0 12px;
        }
        .meta-grid { width: 100%; border-collapse: collapse; }
        .meta-grid td { padding: 2px 0; vertical-align: top; }
        .meta-grid td:first-child { width: 120px; color: #6b7280; }

        .summary-grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .summary-grid td { padding: 5px 6px; border: 1px solid #e5e7eb; }
        .summary-grid td.label { width: 55%; color: #374151; }
        .summary-grid td.val { text-align: right; font-weight: 600; }
        .summary-grid tr.total td { background: #f3f4f6; font-weight: 700; }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* penting: cegah overflow */
        }
        table.report th, table.report td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            vertical-align: top;
            word-break: break-word; /* penting: wrap text */
        }
        table.report th {
            background: #f3f4f6;
            font-weight: 700;
        }
        th.num, td.num { text-align: right; white-space: nowrap; } /* angka aman */
        .footer { margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #6b7280; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 999px; background: #f3f4f6; font-size: 9px; }
    </style>
</head>
<body>
@php
    $fmt = fn(int $v) => number_format($v, 0, ',', '.');
@endphp

<div class="header">
    <h1>Laporan Laba</h1>
    <div class="muted">APP KASIR · Laporan Laba</div>
</div>

<div class="meta">
    <table class="meta-grid">
        <tr><td>Dibuat pada</td><td>: <b>{{ $generated_at }}</b></td></tr>
        <tr><td>Periode</td><td>: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></td></tr>
        <tr><td>Granularitas</td><td>: <span class="badge">{{ $filters['granularity'] }}</span></td></tr>
    </table>
</div>

<h2>Ringkasan</h2>
<table class="summary-grid">
    <tr><td class="label">Pendapatan Part</td><td class="val">{{ $fmt($result->summary->revenuePart) }}</td></tr>
    <tr><td class="label">Pendapatan Jasa</td><td class="val">{{ $fmt($result->summary->revenueService) }}</td></tr>
    <tr><td class="label">Pembulatan</td><td class="val">{{ $fmt($result->summary->roundingAmount) }}</td></tr>
    <tr class="total"><td class="label">Total Pendapatan</td><td class="val">{{ $fmt($result->summary->revenueTotal) }}</td></tr>
    <tr><td class="label">Total HPP</td><td class="val">{{ $fmt($result->summary->cogsTotal) }}</td></tr>
    <tr><td class="label">Total Operasional</td><td class="val">{{ $fmt($result->summary->expensesTotal) }}</td></tr>
    <tr><td class="label">Total Payroll (Kotor)</td><td class="val">{{ $fmt($result->summary->payrollGross) }}</td></tr>
    <tr class="total"><td class="label">Laba Bersih</td><td class="val">{{ $fmt($result->summary->netProfit) }}</td></tr>
    <tr><td class="label">Qty HPP Hilang</td><td class="val">{{ $result->summary->missingCogsQty }}</td></tr>
</table>

<h2>Detail ({{ $result->granularity }})</h2>
<table class="report">
    <thead>
    <tr>
        <th style="width: 14%;">Periode</th>
        <th class="num" style="width: 9%;">Part</th>
        <th class="num" style="width: 9%;">Jasa</th>
        <th class="num" style="width: 7%;">Pembulatan</th>
        <th class="num" style="width: 10%;">Pendapatan</th>
        <th class="num" style="width: 9%;">HPP</th>
        <th class="num" style="width: 9%;">Operasional</th>
        <th class="num" style="width: 9%;">Payroll</th>
        <th class="num" style="width: 10%;">Bersih</th>
        <th class="num" style="width: 4%;">Hilang</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td><b>{{ $r->periodLabel }}</b></td>
            <td class="num">{{ $fmt($r->revenuePart) }}</td>
            <td class="num">{{ $fmt($r->revenueService) }}</td>
            <td class="num">{{ $fmt($r->roundingAmount) }}</td>
            <td class="num"><b>{{ $fmt($r->revenueTotal) }}</b></td>
            <td class="num">{{ $fmt($r->cogsTotal) }}</td>
            <td class="num">{{ $fmt($r->expensesTotal) }}</td>
            <td class="num">{{ $fmt($r->payrollGross) }}</td>
            <td class="num"><b>{{ $fmt($r->netProfit) }}</b></td>
            <td class="num">{{ $r->missingCogsQty }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">Dicetak dari APP KASIR · Laporan Laba</div>
</body>
</html>