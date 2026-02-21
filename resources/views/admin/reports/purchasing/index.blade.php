<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchasing Report</title>
</head>
<body>
<h1>Purchasing Report</h1>

@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<form method="get" action="/admin/reports/purchasing">
    <fieldset>
        <legend>Filter</legend>

        <div>
            <label>From</label>
            <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>

        <div>
            <label>To</label>
            <input type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>

        <div>
            <label>No Faktur (contains)</label>
            <input type="text" name="no_faktur" value="{{ $filters['no_faktur'] ?? '' }}" maxlength="64">
        </div>

        <div>
            <label>Limit</label>
            <input type="number" name="limit" min="1" max="1000" value="{{ $filters['limit'] ?? 200 }}">
        </div>

        <div>
            <button type="submit">Apply</button>
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a target="_blank"
                   href="/admin/reports/purchasing/pdf?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                    Export PDF
                </a>
            @endif
        </div>
    </fieldset>
</form>

<hr>

@if ($result === null)
    <p>Isi periode (from/to) untuk menampilkan data.</p>
@else
    <h2>Ringkasan</h2>
    <ul>
        <li>Count: {{ $result->summary->count }}</li>
        <li>Total Bruto: {{ $fmt($result->summary->totalBruto) }}</li>
        <li>Total Diskon: {{ $fmt($result->summary->totalDiskon) }}</li>
        <li>Total Pajak: {{ $fmt($result->summary->totalPajak) }}</li>
        <li><b>Grand Total: {{ $fmt($result->summary->grandTotal) }}</b></li>
    </ul>

    <h2>Detail</h2>
    <table border="1" cellpadding="6" cellspacing="0">
        <thead>
        <tr>
            <th>Tgl Kirim</th>
            <th>No Faktur</th>
            <th>Supplier</th>
            <th>Total Bruto</th>
            <th>Total Diskon</th>
            <th>Total Pajak</th>
            <th>Grand Total</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($result->rows as $r)
            <tr>
                <td>{{ $r->tglKirim }}</td>
                <td>{{ $r->noFaktur }}</td>
                <td>{{ $r->supplierName }}</td>
                <td>{{ $fmt($r->totalBruto) }}</td>
                <td>{{ $fmt($r->totalDiskon) }}</td>
                <td>{{ $fmt($r->totalPajak) }}</td>
                <td><b>{{ $fmt($r->grandTotal) }}</b></td>
            </tr>
        @endforeach
        </tbody>
    </table>
@endif

</body>
</html>