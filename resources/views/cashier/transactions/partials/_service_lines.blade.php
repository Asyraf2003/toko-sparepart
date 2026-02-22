<div class="card mt-3">
    <div class="card-header">
        <h4>Service Lines</h4>
    </div>
    <div class="card-body">
        <h6>Tambah Service</h6>

        <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines') }}" class="row g-2 align-items-end">
            @csrf

            {{-- ✅ default reason, kasir tidak perlu isi --}}
            <input type="hidden" name="reason" value="Tambah service">

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
                                {{-- UPDATE --}}
                                <form method="post"
                                      action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/update') }}"
                                      class="row g-2 align-items-end">
                                    @csrf

                                    {{-- ✅ default reason --}}
                                    <input type="hidden" name="reason" value="Update service #{{ $s->id }}">

                                    <div class="col-6">
                                        <label class="form-label">Deskripsi</label>
                                        <input type="text" name="description" value="{{ $s->description }}" class="form-control form-control-sm" required>
                                    </div>

                                    <div class="col-3">
                                        <label class="form-label">Harga</label>
                                        <input type="number" name="price_manual" min="0" value="{{ $s->price_manual }}" class="form-control form-control-sm" required>
                                    </div>

                                    <div class="col-3">
                                        <button type="submit" class="btn btn-sm btn-outline-primary w-100">Update</button>
                                    </div>
                                </form>

                                {{-- DELETE --}}
                                <form method="post"
                                      action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/delete') }}"
                                      class="row g-2 align-items-end mt-2">
                                    @csrf

                                    {{-- ✅ default reason --}}
                                    <input type="hidden" name="reason" value="Hapus service #{{ $s->id }}">

                                    <div class="col-12">
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