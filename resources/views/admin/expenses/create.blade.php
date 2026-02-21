<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Expense</title>
</head>
<body>
<div style="max-width:700px;margin:20px auto;">
    <h1>Tambah Expense</h1>

    <p><a href="/admin/expenses">Kembali</a></p>

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

    <form method="post" action="/admin/expenses">
        @csrf

        <p>
            <label>Tanggal<br>
                <input type="date" name="expense_date" value="{{ old('expense_date') }}">
            </label>
        </p>

        <p>
            <label>Kategori (max 64)<br>
                <input type="text" name="category" value="{{ old('category') }}">
            </label>
        </p>

        <p>
            <label>Amount (rupiah integer)<br>
                <input type="number" name="amount" min="0" value="{{ old('amount', '0') }}">
            </label>
        </p>

        <p>
            <label>Note (opsional)<br>
                <input type="text" name="note" value="{{ old('note') }}">
            </label>
        </p>

        <button type="submit">Simpan</button>
        <a href="/admin/expenses">Batal</a>
    </form>
</div>
</body>
</html>