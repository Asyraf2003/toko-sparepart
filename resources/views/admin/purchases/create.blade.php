<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Pembelian</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Tambah Pembelian (Supplier)</h1>

    <p>
        <a href="/admin/purchases">Kembali</a>
        |
        <a href="/admin/products">Produk & Stok</a>
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

    <form method="post" action="/admin/purchases">
        @csrf

        <fieldset>
            <legend>Header</legend>

            <p>
                <label>Supplier Name<br>
                    <input type="text" name="supplier_name" value="{{ old('supplier_name') }}">
                </label>
            </p>

            <p>
                <label>No Faktur (unik)<br>
                    <input type="text" name="no_faktur" value="{{ old('no_faktur') }}">
                </label>
            </p>

            <p>
                <label>Tgl Kirim (Y-m-d)<br>
                    <input type="date" name="tgl_kirim" value="{{ old('tgl_kirim') }}">
                </label>
            </p>

            <p>
                <label>Kepada (opsional)<br>
                    <input type="text" name="kepada" value="{{ old('kepada') }}">
                </label>
            </p>

            <p>
                <label>No Pesanan (opsional)<br>
                    <input type="text" name="no_pesanan" value="{{ old('no_pesanan') }}">
                </label>
            </p>

            <p>
                <label>Nama Sales (opsional)<br>
                    <input type="text" name="nama_sales" value="{{ old('nama_sales') }}">
                </label>
            </p>

            <p>
                <label>Total Pajak (Rupiah integer, header-level)<br>
                    <input type="number" name="total_pajak" min="0" value="{{ old('total_pajak', '0') }}">
                </label>
            </p>

            <p>
                <label>Note (opsional)<br>
                    <input type="text" name="note" value="{{ old('note') }}">
                </label>
            </p>
        </fieldset>

        <fieldset>
            <legend>Lines (isi minimal 1)</legend>

            <table border="1" cellspacing="0" cellpadding="6">
                <thead>
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit Cost</th>
                    <th>Diskon (%)</th>
                </tr>
                </thead>
                <tbody>
                @for ($i = 0; $i < 10; $i++)
                    <tr>
                        <td>
                            <select name="lines[{{ $i }}][product_id]">
                                <option value="">-- pilih --</option>
                                @foreach ($products as $p)
                                    <option value="{{ $p->productId }}"
                                        {{ (string) old("lines.$i.product_id") === (string) $p->productId ? 'selected' : '' }}>
                                        {{ $p->sku }} - {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $i }}][qty]" min="1" value="{{ old("lines.$i.qty") }}">
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $i }}][unit_cost]" min="0" value="{{ old("lines.$i.unit_cost") }}">
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $i }}][disc_percent]" min="0" max="100" step="0.01"
                                   value="{{ old("lines.$i.disc_percent", '0') }}">
                        </td>
                    </tr>
                @endfor
                </tbody>
            </table>
        </fieldset>

        <p>
            <button type="submit">Simpan Pembelian</button>
            <a href="/admin/purchases">Batal</a>
        </p>
    </form>
</div>
</body>
</html>