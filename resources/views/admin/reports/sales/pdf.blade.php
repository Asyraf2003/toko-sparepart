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

    $fmtN = function (?int $v) use ($fmt): string {
        return $v === null ? '-' : $fmt($v);
    };

    $fmtNet = function (?int $received, ?int $change) use ($fmt): string {
        if ($received === null || $change === null) {
            return '-';
        }
        return $fmt($received - $change);
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

    <li><b>Cash Received Total: {{ $fmt($result->summary->cashReceivedTotal) }}</b></li>
    <li><b>Cash Change Total: {{ $fmt($result->summary->cashChangeTotal) }}</b></li>
    <li><b>Cash Net Total: {{ $fmt($result->summary->cashNetTotal) }}</b></li>

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
        <th>Cash Received</th>
        <th>Cash Change</th>
        <th>Cash Net</th>
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
            <td>{{ $fmtN($r->cashReceived) }}</td>
            <td>{{ $fmtN($r->cashChange) }}</td>
            <td>{{ $fmtNet($r->cashReceived, $r->cashChange) }}</td>
            <td>{{ $fmt($r->cogsTotal) }}</td>
            <td>{{ $r->missingCogsQty }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>