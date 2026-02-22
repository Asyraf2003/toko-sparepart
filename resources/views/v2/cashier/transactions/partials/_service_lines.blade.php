<div class="card mt-3">
    <div class="card-header">
        <h4>Service Lines</h4>
    </div>
    <div class="card-body">
        <h6>Tambah Service</h6>

        <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-6">
                <label class="form-label">Deskripsi</label>
                <input type="text" name="description" value="{{ old('description') }}" class="form-control" required>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label">Harga</label>
                <input type="number" name="price_manual" min="0" value="{{ old('price_manual', 0) }}" class="form-control" required>
            </div>

            <div class="col-12 col-md-auto">
                <button type="submit" class="btn btn-outline-primary">Tambah</button>
            </div>
        </form>

        <hr>

        @if($serviceLines->count() === 0)
            <p class="mb-0">-</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-lg">
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
                            <td style="min-width: 380px;">
                                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/update') }}" class="row g-2 align-items-end">
                                    @csrf
                                    <div class="col-5">
                                        <label class="form-label">Deskripsi</label>
                                        <input type="text" name="description" value="{{ $s->description }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-3">
                                        <label class="form-label">Harga</label>
                                        <input type="number" name="price_manual" min="0" value="{{ $s->price_manual }}" class="form-control form-control-sm" required>
                                    </div>
                                    <div class="col-4">
                                        <label class="form-label">Reason</label>
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="reason" required>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                    </div>
                                </form>

                                <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/delete') }}" class="row g-2 align-items-end mt-2">
                                    @csrf
                                    <div class="col-8">
                                        <label class="form-label">Reason</label>
                                        <input type="text" name="reason" class="form-control form-control-sm" placeholder="reason" required>
                                    </div>
                                    <div class="col-4">
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