<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'Laravel') }}</title>
    </head>
    <body>
        <header>
            @if (Route::has('login'))
                <nav>
                    @auth
                        <a href="{{ url('/dashboard') }}">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}">Log in</a>
                    @endauth
                </nav>
            @endif
        </header>
    </body>
</html>