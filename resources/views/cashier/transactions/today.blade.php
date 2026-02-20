<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kasir - Transaksi Hari Ini</title>
</head>
<body>
<div style="max-width:900px;margin:20px auto;">
    <h1>Transaksi Hari Ini ({{ $today }})</h1>

    @if (session('error'))
        <div style="margin:10px 0;border:1px solid #ccc;padding:8px;">
            ERROR: {{ session('error') }}
        </div>
    @endif

    @if (session('status'))
        <div style="margin:10px 0;border:1px solid #ccc;padding:8px;">
            {{ session('status') }}
        </div>
    @endif

    <form method="get" action="/cashier/transactions/today">
        <label>Status:</label>
        <select name="status">
            <option value="" @if(($status ?? '') === '') selected @endif>ALL</option>
            <option value="DRAFT" @if(($status ?? '') === 'DRAFT') selected @endif>DRAFT</option>
            <option value="OPEN" @if(($status ?? '') === 'OPEN') selected @endif>OPEN</option>
            <option value="COMPLETED" @if(($status ?? '') === 'COMPLETED') selected @endif>COMPLETED</option>
            <option value="VOID" @if(($status ?? '') === 'VOID') selected @endif>VOID</option>
        </select>

        <label>Cari No:</label>
        <input type="text" name="q" value="{{ $q ?? '' }}" placeholder="INV-..." />

        <button type="submit">Apply</button>
    </form>

    <div style="margin-top:10px;">
        <form method="post" action="/cashier/transactions">
            @csrf
            <button type="submit">+ Buat Nota Baru (DRAFT)</button>
        </form>
    </div>

    <hr>

    @if($rows->count() === 0)
        <p>Belum ada transaksi hari ini.</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>ID</th>
                <th>No</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Method</th>
                <th>Rounding</th>
                @if($hasCustomerName)
                    <th>Customer</th>
                @endif
                @if($hasVehiclePlate)
                    <th>Plat</th>
                @endif
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach($rows as $r)
                <tr>
                    <td>{{ $r->id }}</td>
                    <td>{{ $r->transaction_number }}</td>
                    <td>{{ $r->status }}</td>
                    <td>{{ $r->payment_status }}</td>
                    <td>{{ $r->payment_method ?? '-' }}</td>
                    <td>{{ $r->rounding_amount ?? 0 }}</td>
                    @if($hasCustomerName)
                        <td>{{ $r->customer_name ?? '-' }}</td>
                    @endif
                    @if($hasVehiclePlate)
                        <td>{{ $r->vehicle_plate ?? '-' }}</td>
                    @endif
                    <td><a href="/cashier/transactions/{{ $r->id }}">Buka</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <hr>

    <form method="post" action="/logout">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>
</body>
</html>