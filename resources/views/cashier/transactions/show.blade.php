<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kasir - Detail Nota</title>
</head>
<body>
<div style="max-width:900px;margin:20px auto;">
    <p><a href="/cashier/transactions/today">&larr; Kembali</a></p>

    <h1>Detail Nota</h1>

    @include('cashier.transactions.partials._alerts')

    <p><b>ID:</b> {{ $tx->id }}</p>
    <p><b>No:</b> {{ $tx->transaction_number }}</p>
    <p><b>Business Date:</b> {{ $tx->business_date }}</p>
    <p><b>Status:</b> {{ $tx->status }}</p>
    <p><b>Payment Status:</b> {{ $tx->payment_status }}</p>
    <p><b>Payment Method:</b> {{ $tx->payment_method ?? '-' }}</p>
    <p><b>Rounding:</b> {{ $tx->rounding_amount ?? 0 }}</p>

    <hr>

    @include('cashier.transactions.partials._summary_actions')

    @include('cashier.transactions.partials._cash_calculator')

    @include('cashier.transactions.partials._product_search')

    @include('cashier.transactions.partials._part_lines')

    @include('cashier.transactions.partials._service_lines')
</div>
</body>
</html>