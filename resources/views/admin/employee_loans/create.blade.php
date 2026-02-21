<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Loan</title>
</head>
<body>
<div style="max-width:700px;margin:20px auto;">
    <h1>Tambah Loan — {{ $employee->name }}</h1>

    <p><a href="/admin/employees">Kembali</a></p>

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

    <form method="post" action="/admin/employees/{{ $employee->id }}/loans">
        @csrf

        <p>
            <label>Tanggal Loan<br>
                <input type="date" name="loan_date" value="{{ old('loan_date') }}">
            </label>
        </p>

        <p>
            <label>Amount (rupiah integer)<br>
                <input type="number" name="amount" min="1" value="{{ old('amount', '0') }}">
            </label>
        </p>

        <p>
            <label>Note (opsional)<br>
                <input type="text" name="note" value="{{ old('note') }}">
            </label>
        </p>

        <button type="submit">Simpan Loan</button>
        <a href="/admin/employees">Batal</a>
    </form>
</div>
</body>
</html>