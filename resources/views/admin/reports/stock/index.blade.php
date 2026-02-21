<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Stock Report</title>
</head>
<body>
<h1>Stock Report</h1>

<form method="get" action="/admin/reports/stock">
    <fieldset>
        <legend>Filter</legend>

        <div>
            <label>Search (sku/nama)</label>
            <input type="text" name="q" value="{{ $filters['q'] ?? '' }}" maxlength="190">
        </div>

        <div>
            <label>Only Active</label>
            <select name="only_active">
                <option value="1" @selected(($filters['only_active'] ?? '1') === '1')>Yes</option>
                <option value="0" @selected(($filters['only_active'] ?? '1') === '0')>No</option>
            </select>
        </div>

        <div>
            <label>Limit</label>
            <input type="number" name="limit" min="1" max="2000" value="{{ $filters['limit'] ?? 500 }}">
        </div>

        <div>
            <button type="submit">Apply</button>
            <a target="_blank"
               href="/admin/reports/stock/pdf?{{ http_build_query(array_filter($filters, fn($v) => $v !== null && $v !== '')) }}">
                Export PDF
            </a>
        </div>
    </fieldset>
</form>

<hr>

<h2>Ringkasan</h2>
<ul>
    <li>Count: {{ $result->summary->count }}</li>
    <li>Low Stock Count (available <= threshold): {{ $result->summary->lowStockCount }}</li>
</ul>

<h2>Detail</h2>
<table border="1" cellpadding="6" cellspacing="0">
    <thead>
    <tr>
        <th>SKU</th>
        <th>Nama</th>
        <th>Active</th>
        <th>Threshold</th>
        <th>On Hand</th>
        <th>Reserved</th>
        <th>Available</th>
        <th>Low Stock</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($result->rows as $r)
        <tr>
            <td>{{ $r->sku }}</td>
            <td>{{ $r->name }}</td>
            <td>{{ $r->isActive ? 'YES' : 'NO' }}</td>
            <td>{{ $r->minStockThreshold }}</td>
            <td>{{ $r->onHandQty }}</td>
            <td>{{ $r->reservedQty }}</td>
            <td><b>{{ $r->availableQty }}</b></td>
            <td>{{ $r->isLowStock ? 'YES' : 'NO' }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>