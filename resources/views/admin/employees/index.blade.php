<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Employees</title>
</head>
<body>
<div style="max-width:900px;margin:20px auto;">
    <h1>Employees</h1>

    <p>
        <a href="/admin/employees/create">Tambah Employee</a>
        |
        <a href="/admin/payroll">Payroll</a>
        |
        <a href="/admin/expenses">Expenses</a>
        |
        <a href="/admin/products">Produk & Stok</a>
    </p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>Nama</th>
            <th>Aktif?</th>
            <th>Outstanding Loan</th>
            <th>Aksi</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($rows as $r)
            <tr>
                <td>{{ $r->name }}</td>
                <td>{{ $r->is_active ? 'YES' : 'NO' }}</td>
                <td>{{ (int) ($outstandingByEmployeeId[$r->id] ?? 0) }}</td>
                <td>
                    <a href="/admin/employees/{{ $r->id }}/loans/create">Tambah Loan</a>
                </td>
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