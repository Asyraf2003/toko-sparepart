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
                            <td style="min-width: 240px;">
                                <div class="d-flex align-items-center gap-2">

                                    {{-- QTY --}}
                                    <input type="number"
                                        name="qty"
                                        min="1"
                                        value="{{ $l->qty }}"
                                        class="form-control form-control-sm"
                                        style="width: 90px;"
                                        form="partline-qty-{{ $l->id }}"
                                        aria-label="Qty"
                                        required>

                                    {{-- UPDATE --}}
                                    <form id="partline-qty-{{ $l->id }}"
                                        method="post"
                                        action="{{ url('/cashier/transactions/'.$tx->id.'/part-lines/'.$l->id.'/qty') }}"
                                        class="m-0 p-0">
                                        @csrf
                                        <input type="hidden" name="reason" value="Update qty {{ $l->sku }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-warning p-0 d-flex align-items-center justify-content-center"
                                                style="width: 30px; height: 30px;"
                                                title="Update Qty"
                                                aria-label="Update Qty">
                                            <i class="bi bi-pencil-square"
                                            style="font-size: 18px; line-height: 1;"></i>
                                        </button>
                                    </form>

                                    {{-- DELETE --}}
                                    <form method="post"
                                        action="{{ url('/cashier/transactions/'.$tx->id.'/part-lines/'.$l->id.'/delete') }}"
                                        class="m-0 p-0">
                                        @csrf
                                        <input type="hidden" name="reason" value="Hapus line {{ $l->sku }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-danger p-0 d-flex align-items-center justify-content-center"
                                                style="width: 30px; height: 30px;"
                                                title="Hapus"
                                                aria-label="Hapus">
                                            <i class="bi bi-trash"
                                            style="font-size: 18px; line-height: 1;"></i>
                                        </button>
                                    </form>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>