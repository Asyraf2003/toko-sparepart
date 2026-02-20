<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Produk & Stok</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Produk dan Stok</h1>

    <p><a href="/admin/products/create">Tambah Produk</a></p>

    <form method="get" action="/admin/products">
        <label>
            Cari (SKU/Nama):
            <input type="text" name="q" value="{{ $q }}">
        </label>
        <button type="submit">Cari</button>
        <a href="/admin/products">Reset</a>
    </form>

    <p>Total: {{ count($rows) }}</p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>SKU</th>
            <th>Nama</th>
            <th>Harga</th>
            <th>On Hand</th>
            <th>Reserved</th>
            <th>Available</th>
            <th>Min</th>
            <th>Low?</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $r->sku }}</td>
                <td>{{ $r->name }}</td>
                <td>{{ $r->sellPriceCurrent }}</td>
                <td>{{ $r->onHandQty }}</td>
                <td>{{ $r->reservedQty }}</td>
                <td>{{ $r->availableQty() }}</td>
                <td>{{ $r->minStockThreshold }}</td>
                <td>{{ $r->isLowStock() ? 'YES' : 'NO' }}</td>
                <td><a href="/admin/products/{{ $r->productId }}/edit">Edit</a></td>
            </tr>
        @empty
            <tr>
                <td colspan="9">Tidak ada data</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <p><a href="/logout" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Logout</a></p>
    <form id="logout-form" method="post" action="/logout">
        @csrf
    </form>
</div>
</body>
</html>