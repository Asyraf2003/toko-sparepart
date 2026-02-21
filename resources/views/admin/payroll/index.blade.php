<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payroll</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Payroll (Mingguan: Senin–Sabtu)</h1>

    <p>
        <a href="/admin/payroll/create">Buat Payroll Period</a>
        |
        <a href="/admin/employees">Employees</a>
        |
        <a href="/admin/expenses">Expenses</a>
        |
        <a href="/admin/products">Produk & Stok</a>
    </p>

    <table border="1" cellspacing="0" cellpadding="6">
        <thead>
        <tr>
            <th>Week Start</th>
            <th>Week End</th>
            <th>Gross</th>
            <th>Deduction</th>
            <th>Net</th>
            <th>Deductions Applied?</th>
            <th>Note</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($periods as $p)
            @php
                $s = $sumByPeriodId[$p->id] ?? null;
                $gross = $s ? (int) $s->sum_gross : 0;
                $ded = $s ? (int) $s->sum_deduction : 0;
                $net = $s ? (int) $s->sum_net : 0;
            @endphp
            <tr>
                <td>{{ $p->week_start }}</td>
                <td>{{ $p->week_end }}</td>
                <td>{{ $gross }}</td>
                <td>{{ $ded }}</td>
                <td>{{ $net }}</td>
                <td>{{ $p->loan_deductions_applied_at ? 'YES' : 'NO' }}</td>
                <td>{{ $p->note }}</td>
            </tr>
        @empty
            <tr><td colspan="7">Tidak ada data</td></tr>
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