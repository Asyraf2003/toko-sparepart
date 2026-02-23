<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
    <style>
        @page { size: A4 portrait; margin: 14mm 12mm; }

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
        .badge { display: inline-block; padding: 2px 7px; border-radius: 999px; background: #f3f4f6; font-size: 9px; }

        .summary-grid { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .summary-grid td { padding: 5px 6px; border: 1px solid #e5e7eb; }
        .summary-grid td.label { width: 65%; color: #374151; }
        .summary-grid td.val { text-align: right; font-weight: 600; }

        table.report {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed; /* penting */
        }
        table.report th, table.report td {
            border: 1px solid #e5e7eb;
            padding: 4px 5px;
            vertical-align: top;
            word-break: break-word;
        }
        table.report th { background: #f3f4f6; font-weight: 700; }
        th.num, td.num { text-align: right; white-space: nowrap; }

        .footer { margin-top: 12px; border-top: 1px solid #e5e7eb; padding-top: 6px; font-size: 9px; color: #6b7280; }
    </style>
</head>
<body>
<div class="header">
    <h1>Stock Report</h1>
    <div class="muted">APP KASIR · Laporan Stok</div>
</div>

<div class="meta">
    <table class="meta-grid">
        <tr><td>Generated at</td><td>: <b>{{ $generated_at }}</b></td></tr>
        <tr>
            <td>Filter</td>
            <td>:
                <span class="badge">q={{ $filters['q'] ?? '' }}</span>
                <span class="badge">only_active={{ $filters['only_active'] ?? '1' }}</span>
            </td>
        </tr>
    </table>
</div>

<h2>Ringkasan</h2>
<table class="summary-grid">
    <tr><td class="label">Count</td><td class="val">{{ $result->summary->count }}</td></tr>
    <tr><td class="label">Low Stock Count (available &lt;= threshold)</td><td class="val">{{ $result->summary->lowStockCount }}</td></tr>
</table>

<h2>Detail</h2>
<table class="report">
    <thead>
    <tr>
        <th style="width: 16%;">SKU</th>
        <th style="width: 30%;">Nama</th>
        <th style="width: 8%;">Act</th>
        <th class="num" style="width: 10%;">Thr</th>
        <th class="num" style="width: 10%;">On</th>
        <th class="num" style="width: 10%;">Res</th>
        <th class="num" style="width: 10%;">Avail</th>
        <th style="width: 6%;">Low</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td><b>{{ $r->sku }}</b></td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->isActive ? 'Y' : 'N' }}</td>
            <td class="num">{{ $r->minStockThreshold }}</td>
            <td class="num">{{ $r->onHandQty }}</td>
            <td class="num">{{ $r->reservedQty }}</td>
            <td class="num"><b>{{ $r->availableQty }}</b></td>
            <td>{{ $r->isLowStock ? 'Y' : 'N' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<div class="footer">Dicetak dari APP KASIR · Stock Report</div>
</body>
</html>