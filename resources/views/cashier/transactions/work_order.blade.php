<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Work Order</title>
</head>
<body>
<div style="max-width:800px;margin:20px auto;">
    <h1>WORK ORDER</h1>

    <div>No: <b>{{ $tx->transaction_number }}</b></div>
    <div>Tanggal: {{ $tx->business_date }}</div>
    <div>Status: {{ $tx->status }}</div>

    <hr>

    <h3>Customer</h3>
    <div>Nama: {{ $tx->customer_name ?? '-' }}</div>
    <div>HP: {{ $tx->customer_phone ?? '-' }}</div>
    <div>No Polisi: {{ $tx->vehicle_plate ?? '-' }}</div>

    <hr>

    <h3>Service</h3>
    @if($services->count() === 0)
        <p>-</p>
    @else
        <ol>
            @foreach($services as $s)
                <li>{{ $s->description }}</li>
            @endforeach
        </ol>
    @endif

    <hr>

    <h3>Sparepart (dipakai/ditahan)</h3>
    @if($parts->count() === 0)
        <p>-</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Nama</th>
                <th>Qty</th>
            </tr>
            </thead>
            <tbody>
            @foreach($parts as $p)
                <tr>
                    <td>{{ $p->sku }}</td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->qty }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <hr>

    <div>Catatan: ____________________________</div>
    <div style="margin-top:10px;">Tanda tangan: _______________________</div>
</div>
</body>
</html>