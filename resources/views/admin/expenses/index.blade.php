<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Expenses</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Expenses</h1>

    <p>
        <a href="/admin/expenses/create">Tambah Expense</a>
        |
        <a href="/admin/payroll">Payroll</a>
        |
        <a href="/admin/employees">Employees</a>
        |
        <a href="/admin/products">Produk & Stok</a>
    </p>

    <form method="get" action="/admin/expenses">
        <label>
            Cari kategori:
            <input type="text" name="q" value="{{ $q }}">
        </label>
        <button type="submit">Cari</button>
        <a href="/admin/expenses">Reset</a>
    </form>

    <p>Total: {{ count($rows) }}</p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Amount</th>
            <th>Note</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $r->expense_date }}</td>
                <td>{{ $r->category }}</td>
                <td>{{ $r->amount }}</td>
                <td>{{ $r->note }}</td>
            </tr>
        @empty
            <tr><td colspan="4">Tidak ada data</td></tr>
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