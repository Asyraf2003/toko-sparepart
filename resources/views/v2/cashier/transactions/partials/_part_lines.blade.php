<div class="card mt-3">
    <div class="card-header">
        <h4>Sparepart Lines</h4>
    </div>
    <div class="card-body">
        @if($partLines->count() === 0)
            <p class="mb-0">-</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-lg">
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
                            <td style="min-width: 320px;">
                                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/part-lines/'.$l->id.'/qty') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-4">
                                        <label class="form-label">Qty</label>
                                        <input type="number" name="qty" min="1" value="{{ $l->qty }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-5">
                                        <label class="form-label">Reason</label>
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="reason" required>
                                    </div>
                                    <div class="col-3">
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Update</button>
                                    </div>
                                </form>

                                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/part-lines/'.$l->id.'/delete') }}" class="row g-2 align-items-end mt-2">
                                    @csrf
                                    <div class="col-9">
                                        <label class="form-label">Reason</label>
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="reason" required>
                                    </div>
                                    <div class="col-3">
                                        <button type="submit" class="btn btn-sm btn-outline-danger w-100">Hapus</button>
                                    </div>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>