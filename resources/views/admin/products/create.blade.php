<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Produk</title>
</head>
<body>
<div style="max-width:700px;margin:20px auto;">
    <h1>Tambah Produk</h1>

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

    <form method="post" action="/admin/products">
        @csrf

        <p>
            <label>SKU<br>
                <input type="text" name="sku" value="{{ old('sku') }}">
            </label>
        </p>

        <p>
            <label>Nama<br>
                <input type="text" name="name" value="{{ old('name') }}">
            </label>
        </p>

        <p>
            <label>Harga Jual (integer)<br>
                <input type="number" name="sell_price_current" value="{{ old('sell_price_current', '0') }}">
            </label>
        </p>

        <p>
            <label>Min Stock Threshold<br>
                <input type="number" name="min_stock_threshold" value="{{ old('min_stock_threshold', '3') }}">
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                Aktif
            </label>
        </p>

        <button type="submit">Simpan</button>
        <a href="/admin/products">Batal</a>
    </form>
</div>
</body>
</html>