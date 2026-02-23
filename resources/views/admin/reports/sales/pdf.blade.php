<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
    <style>
        @page { size: A4 landscape; margin: 12mm 10mm; }

        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
            font-size: 9px;
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
        .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; background: #f3f4f6; font-size: 8px; margin-right: 4px; }

        .summary-grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .summary-grid td { padding: 5px 6px; border: 1px solid #e5e7eb; }
        .summary-grid td.label { width: 55%; color: #374151; }
        .summary-grid td.val { text-align: right; font-weight: 600; }
        .summary-grid tr.total td { background: #f3f4f6; font-weight: 700; }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* penting */
        }
        table.report th, table.report td {
            border: 1px solid #e5e7eb;
            padding: 3px 4px; /* lebih rapat */
            vertical-align: top;
            word-break: break-word;
        }
        table.report th { background: #f3f4f6; font-weight: 700; }
        th.num, td.num { text-align: right; white-space: nowrap; }
        .footer { margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 8px; color: #6b7280; }
    </style>
</head>
<body>
@php
    $fmt = fn(int $v) => number_format($v, 0, ',', '.');
    $fmtN = fn(?int $v) => $v === null ? '-' : $fmt($v);
    $fmtNet = function (?int $received, ?int $change) use ($fmt): string {
        if ($received === null || $change === null) return '-';
        return $fmt($received - $change);
    };
@endphp

<div class="header">
    <h1>Laporan Penjualan</h1>
    <div class="muted">APP KASIR · Laporan Penjualan</div>
</div>

<div class="meta">
    <table class="meta-grid">
        <tr><td>Dibuat pada</td><td>: <b>{{ $generated_at }}</b></td></tr>
        <tr><td>Periode</td><td>: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></td></tr>
        <tr>
            <td>Filter</td>
            <td>:
                <span class="badge">status={{ $filters['status'] ?? '(semua)' }}</span>
                <span class="badge">status_bayar={{ $filters['payment_status'] ?? '(semua)' }}</span>
                <span class="badge">metode_bayar={{ $filters['payment_method'] ?? '(semua)' }}</span>
                <span class="badge">kasir={{ $filters['cashier_user_id'] ?? '(semua)' }}</span>
                <span class="badge">batas={{ $filters['limit'] ?? 200 }}</span>
            </td>
        </tr>
    </table>
</div>

<h2>Ringkasan</h2>
<table class="summary-grid">
    <tr><td class="label">Jumlah</td><td class="val">{{ $result->summary->count }}</td></tr>
    <tr><td class="label">Pendapatan Part</td><td class="val">{{ $fmt($result->summary->partSubtotal) }}</td></tr>
    <tr><td class="label">Pendapatan Jasa</td><td class="val">{{ $fmt($result->summary->serviceSubtotal) }}</td></tr>
    <tr><td class="label">Pembulatan</td><td class="val">{{ $fmt($result->summary->roundingAmount) }}</td></tr>
    <tr class="total"><td class="label">Total Akhir</td><td class="val">{{ $fmt($result->summary->grandTotal) }}</td></tr>
    <tr><td class="label">Total Tunai Diterima</td><td class="val">{{ $fmt($result->summary->cashReceivedTotal) }}</td></tr>
    <tr><td class="label">Total Kembalian Tunai</td><td class="val">{{ $fmt($result->summary->cashChangeTotal) }}</td></tr>
    <tr class="total"><td class="label">Total Bersih Tunai</td><td class="val">{{ $fmt($result->summary->cashNetTotal) }}</td></tr>
    <tr><td class="label">Total HPP</td><td class="val">{{ $fmt($result->summary->cogsTotal) }}</td></tr>
    <tr><td class="label">Qty HPP Hilang</td><td class="val">{{ $result->summary->missingCogsQty }}</td></tr>
</table>

<h2>Detail</h2>
<table class="report">
    <thead>
    <tr>
        <th style="width: 8%;">Tanggal</th>
        <th style="width: 10%;">No</th>
        <th style="width: 6%;">St</th>
        <th style="width: 7%;">Bayar</th>
        <th style="width: 7%;">Metode</th>
        <th class="num" style="width: 5%;">Kasir</th>
        <th class="num" style="width: 7%;">Part</th>
        <th class="num" style="width: 7%;">Jasa</th>
        <th class="num" style="width: 6%;">Bulat</th>
        <th class="num" style="width: 7%;">Total</th>
        <th class="num" style="width: 7%;">Diterima</th>
        <th class="num" style="width: 6%;">Kembali</th>
        <th class="num" style="width: 7%;">Bersih</th>
        <th class="num" style="width: 7%;">HPP</th>
        <th class="num" style="width: 6%;">Hilang</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td>{{ $r->businessDate }}</td>
            <td><b>{{ $r->transactionNumber }}</b></td>
            <td>{{ $r->status }}</td>
            <td>{{ $r->paymentStatus }}</td>
            <td>{{ $r->paymentMethod ?? '-' }}</td>
            <td class="num">{{ $r->cashierUserId }}</td>
            <td class="num">{{ $fmt($r->partSubtotal) }}</td>
            <td class="num">{{ $fmt($r->serviceSubtotal) }}</td>
            <td class="num">{{ $fmt($r->roundingAmount) }}</td>
            <td class="num"><b>{{ $fmt($r->grandTotal) }}</b></td>
            <td class="num">{{ $fmtN($r->cashReceived) }}</td>
            <td class="num">{{ $fmtN($r->cashChange) }}</td>
            <td class="num">{{ $fmtNet($r->cashReceived, $r->cashChange) }}</td>
            <td class="num">{{ $fmt($r->cogsTotal) }}</td>
            <td class="num">{{ $r->missingCogsQty }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">Dicetak dari APP KASIR · Laporan Penjualan</div>
</body>
</html>