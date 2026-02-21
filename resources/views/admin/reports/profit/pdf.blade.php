<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Profit Report PDF</title>
</head>
<body>
@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<h1>Profit Report</h1>

<p>Generated at: {{ $generated_at }}</p>
<p>Periode: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></p>
<p>Granularity: {{ $filters['granularity'] }}</p>

<h2>Ringkasan</h2>
<ul>
    <li>Revenue Part: {{ $fmt($result->summary->revenuePart) }}</li>
    <li>Revenue Service: {{ $fmt($result->summary->revenueService) }}</li>
    <li>Rounding: {{ $fmt($result->summary->roundingAmount) }}</li>
    <li><b>Revenue Total: {{ $fmt($result->summary->revenueTotal) }}</b></li>
    <li>COGS Total: {{ $fmt($result->summary->cogsTotal) }}</li>
    <li>Expenses Total: {{ $fmt($result->summary->expensesTotal) }}</li>
    <li>Payroll Gross: {{ $fmt($result->summary->payrollGross) }}</li>
    <li><b>Net Profit: {{ $fmt($result->summary->netProfit) }}</b></li>
    <li>Missing COGS Qty: {{ $result->summary->missingCogsQty }}</li>
</ul>

<h2>Detail ({{ $result->granularity }})</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
    <tr>
        <th>Period</th>
        <th>Revenue Part</th>
        <th>Revenue Service</th>
        <th>Rounding</th>
        <th>Revenue Total</th>
        <th>COGS</th>
        <th>Expenses</th>
        <th>Payroll Gross</th>
        <th>Net Profit</th>
        <th>Missing</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td>{{ $r->periodLabel }}</td>
            <td>{{ $fmt($r->revenuePart) }}</td>
            <td>{{ $fmt($r->revenueService) }}</td>
            <td>{{ $fmt($r->roundingAmount) }}</td>
            <td><b>{{ $fmt($r->revenueTotal) }}</b></td>
            <td>{{ $fmt($r->cogsTotal) }}</td>
            <td>{{ $fmt($r->expensesTotal) }}</td>
            <td>{{ $fmt($r->payrollGross) }}</td>
            <td><b>{{ $fmt($r->netProfit) }}</b></td>
            <td>{{ $r->missingCogsQty }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>