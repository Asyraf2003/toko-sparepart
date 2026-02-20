<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login</title>
</head>
<body>
<div style="max-width:420px;margin:40px auto;">
    <h1>Login</h1>

    @if ($errors->any())
        <div style="margin:12px 0;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/login">
        @csrf

        <div>
            <label for="email">Email</label><br>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
        </div>

        <div style="margin-top:10px;">
            <label for="password">Password</label><br>
            <input id="password" name="password" type="password" required>
        </div>

        <div style="margin-top:10px;">
            <label>
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
        </div>

        <div style="margin-top:14px;">
            <button type="submit">Masuk</button>
        </div>

        <div style="margin-top:10px;">
            <small>Admin: admin@local.test / 12345678</small><br>
            <small>Kasir: cashier@local.test / 12345678</small>
        </div>
    </form>
</div>
</body>
</html>