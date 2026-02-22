@php
    $pqValue = $pq ?? $search ?? $q ?? '';
    $rows = $productRows ?? $products ?? collect();
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Cari Sparepart</h4>
    </div>
    <div class="card-body">
        <form method="get" action="{{ url('/cashier/transactions/'.$tx->id) }}" class="row g-2 align-items-end">
            <div class="col-12 col-md-6">
                <label class="form-label">Cari (SKU/Nama)</label>
                <input type="text" name="pq" value="{{ $pqValue }}" class="form-control">
                <input type="hidden" name="q" value="{{ $pqValue }}">
            </div>

            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        @if($rows->count() > 0)
            <div class="table-responsive mt-3">
                <table class="table table-hover table-lg">
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
                                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/part-lines') }}" class="d-flex gap-2">
                                    @csrf
                                    <input type="hidden" name="product_id" value="{{ $p->id }}">
                                    <input type="number" name="qty" value="1" min="1" class="form-control form-control-sm" style="width: 90px;">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Tambah</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="mt-3 mb-0">Tidak ada hasil (atau belum ada produk).</p>
        @endif
    </div>
</div>