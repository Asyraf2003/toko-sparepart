<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Purchasing Report PDF</title>
</head>
<body>
@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<h1>Purchasing Report</h1>

<p>Generated at: {{ $generated_at }}</p>
<p>Periode: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></p>
<p>Filter: no_faktur={{ $filters['no_faktur'] ?? '(all)' }}</p>

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

</body>
</html>