<hr>
<h2>Service Lines</h2>

<h3>Tambah Service</h3>
<form method="post" action="/cashier/transactions/{{ $tx->id }}/service-lines">
    @csrf
    <label>Deskripsi:</label>
    <input type="text" name="description" value="{{ old('description') }}" required>

    <label>Harga:</label>
    <input type="number" name="price_manual" min="0" value="{{ old('price_manual', 0) }}" required>

    <button type="submit">Tambah</button>
</form>

@if($serviceLines->count() === 0)
    <p>-</p>
@else
    <table border="1" cellpadding="6" cellspacing="0" width="100%" style="margin-top:10px;">
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