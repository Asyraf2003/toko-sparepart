<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pembelian (Supplier)</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Pembelian (Supplier)</h1>

    <p>
        <a href="/admin/purchases/create">Tambah Pembelian</a>
        |
        <a href="/admin/products">Produk & Stok</a>
    </p>

    <form method="get" action="/admin/purchases">
        <label>
            Cari (No Faktur / Supplier):
            <input type="text" name="q" value="{{ $q }}">
        </label>
        <button type="submit">Cari</button>
        <a href="/admin/purchases">Reset</a>
    </form>

    <p>Total: {{ count($rows) }}</p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>Tgl Kirim</th>
            <th>No Faktur</th>
            <th>Supplier</th>
            <th>Bruto</th>
            <th>Diskon</th>
            <th>Pajak</th>
            <th>Grand Total</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $r->tgl_kirim }}</td>
                <td>{{ $r->no_faktur }}</td>
                <td>{{ $r->supplier_name }}</td>
                <td>{{ $r->total_bruto }}</td>
                <td>{{ $r->total_diskon }}</td>
                <td>{{ $r->total_pajak }}</td>
                <td>{{ $r->grand_total }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="7">Tidak ada data</td>
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