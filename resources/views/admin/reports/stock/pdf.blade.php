<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Stock Report PDF</title>
</head>
<body>
<h1>Stock Report</h1>

<p>Generated at: {{ $generated_at }}</p>
<p>
    Filter:
    q={{ $filters['q'] ?? '' }},
    only_active={{ $filters['only_active'] ?? '1' }}
</p>

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