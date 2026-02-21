<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Profit Report</title>
</head>
<body>
<h1>Profit Report</h1>

@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<form method="get" action="/admin/reports/profit">
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
            <label>Granularity</label>
            <select name="granularity">
                <option value="weekly" @selected(($filters['granularity'] ?? 'weekly') === 'weekly')>weekly</option>
                <option value="monthly" @selected(($filters['granularity'] ?? 'weekly') === 'monthly')>monthly</option>
            </select>
        </div>

        <div>
            <button type="submit">Apply</button>
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a target="_blank"
                   href="/admin/reports/profit/pdf?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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
            <th>Missing COGS Qty</th>
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
@endif

</body>
</html>