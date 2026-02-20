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

    @if (session('error'))
        <div style="border:1px solid #999;padding:8px;margin:10px 0;">
            <b>Error:</b> {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div style="border:1px solid #999;padding:8px;margin:10px 0;">
            <b>Validation:</b>
            <ul>
                @foreach ($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <p><b>ID:</b> {{ $tx->id }}</p>
    <p><b>No:</b> {{ $tx->transaction_number }}</p>
    <p><b>Business Date:</b> {{ $tx->business_date }}</p>
    <p><b>Status:</b> {{ $tx->status }}</p>
    <p><b>Payment Status:</b> {{ $tx->payment_status }}</p>
    <p><b>Payment Method:</b> {{ $tx->payment_method ?? '-' }}</p>
    <p><b>Rounding:</b> {{ $tx->rounding_amount ?? 0 }}</p>

    <hr>

    <h3>Aksi Nota</h3>

    <div>
        <div>Total Sparepart: {{ $partsTotal }}</div>
        <div>Total Service: {{ $serviceTotal }}</div>
        <div>Grand Total: {{ $grossTotal }}</div>
        <div>Cash Rounded Total: {{ $roundedCashTotal }} (rounding: {{ $cashRoundingAmount }})</div>
    </div>

    @if ($tx->status !== 'VOID')
        <form method="post" action="/cashier/transactions/{{ $tx->id }}/open" style="margin-top:8px;">
            @csrf
            <button type="submit">SIMPAN OPEN (UNPAID)</button>
        </form>

        <form method="post" action="/cashier/transactions/{{ $tx->id }}/complete-cash" style="margin-top:8px;">
            @csrf
            <button type="submit">COMPLETE CASH</button>
        </form>

        <form method="post" action="/cashier/transactions/{{ $tx->id }}/complete-transfer" style="margin-top:8px;">
            @csrf
            <button type="submit">COMPLETE TRANSFER</button>
        </form>

        <form method="post" action="/cashier/transactions/{{ $tx->id }}/void" style="margin-top:8px;">
            @csrf
            <label>Reason VOID:</label>
            <input type="text" name="reason" required>
            <button type="submit">VOID</button>
        </form>
    @endif

    <hr>
    <h3>Pembayaran CASH</h3>

    <div>
        <div>Grand Total: <b>{{ $grossTotal }}</b></div>
        <div>Cash Rounded Total: <b>{{ $roundedCashTotal }}</b> (rounding: {{ $cashRoundingAmount }})</div>
    </div>

    <div style="margin-top:8px;">
        <label>Cash diterima:</label>
        <input id="cash_received" type="number" min="0" value="0" style="width:140px;">
    </div>

    <div style="margin-top:8px;">
        <div>Kembalian (pakai rounded total): <b id="cash_change">0</b></div>
    </div>

    <script>
    (function () {
        var total = {{ (int) ($roundedCashTotal ?? 0) }};
        var input = document.getElementById('cash_received');
        var out = document.getElementById('cash_change');

        function calc() {
            var received = parseInt(input.value || '0', 10);
            if (isNaN(received)) received = 0;
            var change = received - total;
            out.textContent = String(change);
        }

        input.addEventListener('input', calc);
        calc();
    })();
    </script>

    <hr>

    <h3>Cari Sparepart</h3>

    <form method="get" action="/cashier/transactions/{{ $tx->id ?? $transaction->id }}">
        <label>Cari (SKU/Nama):</label>
        <input type="text" name="pq" value="{{ $pq ?? '' }}">
        <button type="submit">Search</button>
    </form>

    @if(isset($productRows) && $productRows->count() > 0)
        <table border="1" cellpadding="6" cellspacing="0" width="100%" style="margin-top:10px;">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Nama</th>
                <th>Harga</th>
                <th>OnHand</th>
                <th>Reserved</th>
                <th>Available</th>
                <th>Tambah</th>
            </tr>
            </thead>
            <tbody>
            @foreach($productRows as $p)
                <tr>
                    <td>{{ $p->sku }}</td>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->sell_price_current }}</td>
                    <td>{{ $p->on_hand_qty }}</td>
                    <td>{{ $p->reserved_qty }}</td>
                    <td>{{ $p->available_qty }}</td>
                    <td>
                        <form method="post" action="/cashier/transactions/{{ $tx->id ?? $transaction->id }}/part-lines">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $p->id }}">
                            <input type="number" name="qty" value="1" min="1" style="width:70px;">
                            <button type="submit">Tambah</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @else
        <p style="margin-top:10px;">Tidak ada hasil (atau belum search).</p>
    @endif

    <h3>Tambah Sparepart</h3>

    <form method="get" action="/cashier/transactions/{{ $tx->id }}">
        <label>Cari produk (sku/nama):</label>
        <input type="text" name="q" value="{{ $search }}">
        <button type="submit">Cari</button>
    </form>

    <form method="post" action="/cashier/transactions/{{ $tx->id }}/part-lines">
        @csrf
        <label>Produk:</label>
        <select name="product_id" required>
            <option value="">-- pilih --</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}">
                    {{ $p->sku }} - {{ $p->name }} (avail: {{ $p->available_qty }}) harga: {{ $p->sell_price_current }}
                </option>
            @endforeach
        </select>

        <label>Qty:</label>
        <input type="number" name="qty" min="1" value="1" required>

        <button type="submit">Tambah</button>
    </form>

    <h2>Sparepart Lines</h2>
    @if($partLines->count() === 0)
        <p>-</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>SKU</th>
                <th>Nama</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
                <th>COGS Frozen</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach($partLines as $l)
                <tr>
                    <td>{{ $l->sku }}</td>
                    <td>{{ $l->name }}</td>
                    <td>{{ $l->qty }}</td>
                    <td>{{ $l->unit_sell_price_frozen }}</td>
                    <td>{{ $l->line_subtotal }}</td>
                    <td>{{ $l->unit_cogs_frozen ?? '-' }}</td>
                    <td>
                        <form method="post" action="/cashier/transactions/{{ $tx->id }}/part-lines/{{ $l->id }}/qty">
                            @csrf
                            <input type="number" name="qty" min="1" value="{{ $l->qty }}" required>
                            <input type="text" name="reason" placeholder="reason" required>
                            <button type="submit">Update Qty</button>
                        </form>

                        <form method="post" action="/cashier/transactions/{{ $tx->id }}/part-lines/{{ $l->id }}/delete" style="margin-top:4px;">
                            @csrf
                            <input type="text" name="reason" placeholder="reason" required>
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <hr>

    <h3>Tambah Service</h3>
    <form method="post" action="/cashier/transactions/{{ $tx->id }}/service-lines">
        @csrf
        <label>Deskripsi:</label>
        <input type="text" name="description" value="{{ old('description') }}" required>

        <label>Harga:</label>
        <input type="number" name="price_manual" min="0" value="{{ old('price_manual', 0) }}" required>

        <button type="submit">Tambah</button>
    </form>

    <h2>Service Lines</h2>
    @if($serviceLines->count() === 0)
        <p>-</p>
    @else
        <table border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
            <tr>
                <th>Deskripsi</th>
                <th>Harga</th>
                <th>Aksi</th>
            </tr>
            </thead>
            <tbody>
            @foreach($serviceLines as $s)
                <tr>
                    <td>{{ $s->description }}</td>
                    <td>{{ $s->price_manual }}</td>
                    <td>
                        <form method="post" action="/cashier/transactions/{{ $tx->id }}/service-lines/{{ $s->id }}/update">
                            @csrf
                            <input type="text" name="description" value="{{ $s->description }}" required>
                            <input type="number" name="price_manual" min="0" value="{{ $s->price_manual }}" required>
                            <input type="text" name="reason" placeholder="reason" required>
                            <button type="submit">Update</button>
                        </form>

                        <form method="post" action="/cashier/transactions/{{ $tx->id }}/service-lines/{{ $s->id }}/delete" style="margin-top:4px;">
                            @csrf
                            <input type="text" name="reason" placeholder="reason" required>
                            <button type="submit">Hapus</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif

    <hr>
    <p>Next step: form add part/service + tombol OPEN/COMPLETE/VOID (reason).</p>
</div>
</body>
</html>