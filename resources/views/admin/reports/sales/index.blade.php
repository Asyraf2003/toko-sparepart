<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
</head>
<body>
<h1>Sales Report</h1>

@php
    $fmt = function (int $v): string {
        return number_format($v, 0, ',', '.');
    };
@endphp

<form method="get" action="/admin/reports/sales">
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
            <label>Status</label>
            <select name="status">
                <option value="">(all)</option>
                @foreach (['DRAFT','OPEN','COMPLETED','VOID'] as $opt)
                    <option value="{{ $opt }}" @selected(($filters['status'] ?? '') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Payment Status</label>
            <select name="payment_status">
                <option value="">(all)</option>
                @foreach (['UNPAID','PAID'] as $opt)
                    <option value="{{ $opt }}" @selected(($filters['payment_status'] ?? '') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Payment Method</label>
            <select name="payment_method">
                <option value="">(all)</option>
                @foreach (['CASH','TRANSFER'] as $opt)
                    <option value="{{ $opt }}" @selected(($filters['payment_method'] ?? '') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label>Cashier User ID</label>
            <input type="number" name="cashier_user_id" min="1" value="{{ $filters['cashier_user_id'] ?? '' }}">
        </div>

        <div>
            <label>Limit</label>
            <input type="number" name="limit" min="1" max="1000" value="{{ $filters['limit'] ?? 200 }}">
        </div>

        <div>
            <button type="submit">Apply</button>
            @if (!empty($filters['from']) && !empty($filters['to']))
                <a target="_blank"
                   href="/admin/reports/sales/pdf?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
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
            <th>Missing COGS Qty</th>
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
@endif

</body>
</html>