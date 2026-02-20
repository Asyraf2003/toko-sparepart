<hr>
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