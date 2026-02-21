<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sales Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 16px; }
        .row { display: flex; gap: 12px; flex-wrap: wrap; align-items: end; }
        label { display:block; font-size: 12px; color:#333; margin-bottom:4px; }
        input, select { padding: 6px 8px; min-width: 180px; }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; }
        th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
        th { background: #f5f5f5; text-align: left; }
        .muted { color:#666; font-size: 12px; }
        .summary { margin-top: 12px; padding: 10px; border: 1px solid #ddd; background:#fafafa; }
        .actions { display:flex; gap:10px; align-items:center; }
        a.btn, button { padding: 8px 10px; border: 1px solid #333; background:#fff; cursor:pointer; text-decoration:none; color:#000; }
    </style>
</head>
<body>
    <h2>Sales Report</h2>
    <p class="muted">Isi periode (from/to) untuk menampilkan data. Export PDF akan memakai filter yang sama.</p>

    <form method="get" action="/admin/reports/sales">
        <div class="row">
            <div>
                <label>From (Y-m-d)</label>
                <input type="date" name="from" value="{{ $filters['from'] ?? '' }}">
            </div>
            <div>
                <label>To (Y-m-d)</label>
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
                <input type="number" name="cashier_user_id" value="{{ $filters['cashier_user_id'] ?? '' }}" min="1" placeholder="e.g. 1">
            </div>

            <div>
                <label>Limit</label>
                <input type="number" name="limit" value="{{ $filters['limit'] ?? 200 }}" min="1" max="1000">
            </div>

            <div class="actions">
                <button type="submit">Apply</button>

                @if (!empty($filters['from']) && !empty($filters['to']))
                    <a class="btn" target="_blank"
                       href="/admin/reports/sales/pdf?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                        Export PDF
                    </a>
                @endif
            </div>
        </div>
    </form>

    @if ($result === null)
        <p class="muted" style="margin-top:14px;">Belum ada hasil karena periode belum diisi.</p>
    @else
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
    @endif
</body>
</html>