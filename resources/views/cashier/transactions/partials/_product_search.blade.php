<hr>
<h3>Cari Sparepart</h3>

<form method="get" action="/cashier/transactions/{{ $tx->id }}">
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
                    <form method="post" action="/cashier/transactions/{{ $tx->id }}/part-lines">
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