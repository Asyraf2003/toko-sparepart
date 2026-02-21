<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Sales Report PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; }
        .header { margin-bottom: 10px; }
        .muted { color:#666; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        th, td { border: 1px solid #999; padding: 6px; font-size: 10px; }
        th { background: #f2f2f2; text-align: left; }
        .summary { margin-top: 10px; padding: 8px; border: 1px solid #999; }
    </style>
</head>
<body>
    <div class="header">
        <div><b>Sales Report</b></div>
        <div class="muted">Generated at: {{ $generated_at }}</div>
        <div>Periode: <b>{{ $filters['from'] }}</b> s/d <b>{{ $filters['to'] }}</b></div>
        <div class="muted">
            Filter:
            status={{ $filters['status'] ?? '(all)' }},
            payment_status={{ $filters['payment_status'] ?? '(all)' }},
            payment_method={{ $filters['payment_method'] ?? '(all)' }},
            cashier_user_id={{ $filters['cashier_user_id'] ?? '(all)' }}
        </div>
    </div>

    <div class="summary">
        <div><b>Summary</b></div>
        <div>Count: {{ $result->summary->count }}</div>
        <div>Revenue Part: {{ $result->summary->partSubtotal }}</div>
        <div>Revenue Service: {{ $result->summary->serviceSubtotal }}</div>
        <div>Rounding: {{ $result->summary->roundingAmount }}</div>
        <div><b>Grand Total: {{ $result->summary->grandTotal }}</b></div>
        <div>COGS Total: {{ $result->summary->cogsTotal }}</div>
        <div>Missing COGS Qty: {{ $result->summary->missingCogsQty }}</div>
    </div>

    <table>
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
                <td>{{ $r->partSubtotal }}</td>
                <td>{{ $r->serviceSubtotal }}</td>
                <td>{{ $r->roundingAmount }}</td>
                <td><b>{{ $r->grandTotal }}</b></td>
                <td>{{ $r->cogsTotal }}</td>
                <td>{{ $r->missingCogsQty }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>