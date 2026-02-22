<div class="card mt-3">
    <div class="card-header">
        <h4>Service Lines</h4>
    </div>
    <div class="card-body">
        <h6>Tambah Service</h6>

        <form method="post" action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines') }}" class="row g-2 align-items-end">
            @csrf
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
                <table class="table table-hover table-lg align-middle">
                    <thead>
                    <tr>
                        <th>Deskripsi</th>
                        <th>Harga</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($serviceLines as $s)
                        <tr>
                            <td>{{ $s->description }}</td>
                                <td><x-ui.rupiah :value="$s->price_manual" /></td>
                                <td style="min-width: 420px;">
                                <div class="d-flex align-items-center justify-content-end gap-2">

                                    {{-- INPUT (Berada di luar form, disambungkan via ID) --}}
                                    <input type="text" 
                                        name="description" 
                                        value="{{ $s->description }}" 
                                        class="form-control form-control-sm" 
                                        style="width: 180px;" 
                                        form="service-update-{{ $s->id }}" 
                                        required>

                                    <input type="number" 
                                        name="price_manual" 
                                        min="0" 
                                        value="{{ $s->price_manual }}" 
                                        class="form-control form-control-sm" 
                                        style="width: 110px;" 
                                        form="service-update-{{ $s->id }}" 
                                        required>

                                    {{-- UPDATE FORM --}}
                                    <form id="service-update-{{ $s->id }}"
                                        method="post"
                                        action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/update') }}"
                                        class="m-0 p-0">
                                        @csrf
                                        <input type="hidden" name="reason" value="Update service #{{ $s->id }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-warning p-0 d-flex align-items-center justify-content-center"
                                                style="width: 30px; height: 30px;"
                                                title="Update Service"
                                                aria-label="Update Service">
                                            <i class="bi bi-pencil-square" style="font-size: 18px; line-height: 1;"></i>
                                        </button>
                                    </form>

                                    {{-- DELETE FORM --}}
                                    <form method="post"
                                        action="{{ url('/cashier/transactions/'.$tx->id.'/service-lines/'.$s->id.'/delete') }}"
                                        class="m-0 p-0">
                                        @csrf
                                        <input type="hidden" name="reason" value="Hapus service #{{ $s->id }}">

                                        <button type="submit"
                                                class="btn btn-sm btn-danger p-0 d-flex align-items-center justify-content-center"
                                                style="width: 30px; height: 30px;"
                                                title="Hapus"
                                                aria-label="Hapus"
                                                onclick="return confirm('Hapus service ini?')">
                                            <i class="bi bi-trash" style="font-size: 18px; line-height: 1;"></i>
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