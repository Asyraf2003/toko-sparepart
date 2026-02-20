<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Produk</title>
</head>
<body>
<div style="max-width:900px;margin:20px auto;">
    <h1>Edit Produk</h1>

    <p>
        <a href="/admin/products">← Kembali</a>
    </p>

    @if ($errors->any())
        <div>
            <p>Validasi error:</p>
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <h2>Info</h2>
    <table border="1" cellspacing="0" cellpadding="6">
        <tr><th>SKU</th><td>{{ $row->sku }}</td></tr>
        <tr><th>Nama</th><td>{{ $row->name }}</td></tr>
        <tr><th>Harga</th><td>{{ $row->sellPriceCurrent }}</td></tr>
        <tr><th>Min</th><td>{{ $row->minStockThreshold }}</td></tr>
        <tr><th>On Hand</th><td>{{ $row->onHandQty }}</td></tr>
        <tr><th>Reserved</th><td>{{ $row->reservedQty }}</td></tr>
        <tr><th>Available</th><td>{{ $row->availableQty() }}</td></tr>
        <tr><th>Low?</th><td>{{ $row->isLowStock() ? 'YES' : 'NO' }}</td></tr>
    </table>

    <h2>Update Info Produk</h2>
    <form method="post" action="/admin/products/{{ $row->productId }}">
        @csrf
        <p>
            <label>SKU<br>
                <input type="text" name="sku" value="{{ $row->sku }}">
            </label>
        </p>
        <p>
            <label>Nama<br>
                <input type="text" name="name" value="{{ $row->name }}">
            </label>
        </p>
        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ $row->isActive ? 'checked' : '' }}>
                Aktif
            </label>
        </p>
        <button type="submit">Update Info</button>
    </form>
    
    <h2>Set Harga Jual</h2>
    <form method="post" action="/admin/products/{{ $row->productId }}/selling-price">
        @csrf
        <p>
            <label>Harga Baru<br>
                <input type="number" name="sell_price_current" value="{{ $row->sellPriceCurrent }}">
            </label>
        </p>
        <p>
            <label>Note/Alasan (wajib)<br>
                <input type="text" name="note" value="">
            </label>
        </p>
        <button type="submit">Update Harga</button>
    </form>

    <h2>Set Min Stock Threshold</h2>
    <form method="post" action="/admin/products/{{ $row->productId }}/min-threshold">
        @csrf
        <p>
            <label>Threshold Baru<br>
                <input type="number" name="min_stock_threshold" value="{{ $row->minStockThreshold }}">
            </label>
        </p>
        <p>
            <label>Note/Alasan (wajib)<br>
                <input type="text" name="note" value="">
            </label>
        </p>
        <button type="submit">Update Threshold</button>
    </form>

    <h2>Adjust Stock (On Hand)</h2>
    <form method="post" action="/admin/products/{{ $row->productId }}/adjust-stock">
        @csrf
        <p>
            <label>Qty Delta (+ tambah / - kurang)<br>
                <input type="number" name="qty_delta" value="0">
            </label>
        </p>
        <p>
            <label>Note/Alasan (wajib)<br>
                <input type="text" name="note" value="">
            </label>
        </p>
        <button type="submit">Adjust</button>
    </form>
</div>
</body>
</html>