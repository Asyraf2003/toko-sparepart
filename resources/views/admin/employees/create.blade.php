<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tambah Employee</title>
</head>
<body>
<div style="max-width:700px;margin:20px auto;">
    <h1>Tambah Employee</h1>

    <p><a href="/admin/employees">Kembali</a></p>

    @if ($errors->any())
        <div>
            <p>Validasi error:</p>
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/admin/employees">
        @csrf

        <p>
            <label>Nama<br>
                <input type="text" name="name" value="{{ old('name') }}">
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}>
                Aktif
            </label>
        </p>

        <button type="submit">Simpan</button>
        <a href="/admin/employees">Batal</a>
    </form>
</div>
</body>
</html>