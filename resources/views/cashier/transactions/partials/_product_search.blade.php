<hr>
<h3>Cari Sparepart</h3>

@php
    $pqValue = $pq ?? $search ?? $q ?? '';
    $rows = $productRows ?? $products ?? collect();
@endphp

<form method="get" action="/cashier/transactions/{{ $tx->id }}">
    <label>Cari (SKU/Nama):</label>
    <input type="text" name="pq" value="{{ $pqValue }}">
    {{-- compatibility kalau controller lama masih baca ?q= --}}
    <input type="hidden" name="q" value="{{ $pqValue }}">
    <button type="submit">Search</button>
</form>

@if($rows->count() > 0)
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
        @foreach($rows as $p)
            @php
                $onHand = isset($p->on_hand_qty) ? (int) $p->on_hand_qty : 0;
                $reserved = isset($p->reserved_qty) ? (int) $p->reserved_qty : 0;
                $avail = isset($p->available_qty) ? (int) $p->available_qty : ($onHand - $reserved);
            @endphp
            <tr>
                <td>{{ $p->sku }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $p->sell_price_current }}</td>
                <td>{{ $onHand }}</td>
                <td>{{ $reserved }}</td>
                <td>{{ $avail }}</td>
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
    <p style="margin-top:10px;">Tidak ada hasil (atau belum ada produk).</p>
@endif