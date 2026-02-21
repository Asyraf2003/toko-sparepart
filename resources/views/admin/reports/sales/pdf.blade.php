<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sales Report PDF</title>
</head>
<body>
@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<h1>Sales Report</h1>

<p>Generated at: {{ $generated_at }}</p>
<p>Periode: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></p>
<p>
    Filter:
    status={{ $filters['status'] ?? '(all)' }},
    payment_status={{ $filters['payment_status'] ?? '(all)' }},
    payment_method={{ $filters['payment_method'] ?? '(all)' }},
    cashier_user_id={{ $filters['cashier_user_id'] ?? '(all)' }}
</p>

<h2>Ringkasan</h2>
<ul>
    <li>Count: {{ $result->summary->count }}</li>
    <li>Revenue Part: {{ $fmt($result->summary->partSubtotal) }}</li>
    <li>Revenue Service: {{ $fmt($result->summary->serviceSubtotal) }}</li>
    <li>Rounding: {{ $fmt($result->summary->roundingAmount) }}</li>
    <li><b>Grand Total: {{ $fmt($result->summary->grandTotal) }}</b></li>
    <li>COGS Total: {{ $fmt($result->summary->cogsTotal) }}</li>
    <li>Missing COGS Qty: {{ $result->summary->missingCogsQty }}</li>
</ul>

<h2>Detail</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
    <tr>
        <th>Date</th>
        <th>No</th>
        <th>Status</th>
        <th>Pay Status</th>
        <th>Pay Method</th>
        <th>Cashier</th>
        <th>Part</th>
        <th>Service</th>
        <th>Rounding</th>
        <th>Grand</th>
        <th>COGS</th>
        <th>Missing</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td>{{ $r->businessDate }}</td>
            <td>{{ $r->transactionNumber }}</td>
            <td>{{ $r->status }}</td>
            <td>{{ $r->paymentStatus }}</td>
            <td>{{ $r->paymentMethod ?? '-' }}</td>
            <td>{{ $r->cashierUserId }}</td>
            <td>{{ $fmt($r->partSubtotal) }}</td>
            <td>{{ $fmt($r->serviceSubtotal) }}</td>
            <td>{{ $fmt($r->roundingAmount) }}</td>
            <td><b>{{ $fmt($r->grandTotal) }}</b></td>
            <td>{{ $fmt($r->cogsTotal) }}</td>
            <td>{{ $r->missingCogsQty }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>