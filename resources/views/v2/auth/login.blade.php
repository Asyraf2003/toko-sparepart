@extends('v2.shared.layouts.base')

@section('title', 'Login')

@push('styles')
    <link rel="stylesheet" href="{{ asset('assets/compiled/css/auth.css') }}">
@endpush

@section('body')
    <div class="container" style="max-width: 420px; margin-top: 40px;">
        <div class="card">
            <div class="card-body">
                <h3 class="mb-3">Login</h3>

                @include('v2.shared.partials._flash')

                <form method="POST" action="{{ url('/login') }}" class="mt-3">
                    @csrf

                    <x-v2.input name="email" label="Email" type="email" :value="old('email')" required autofocus />

                    <div class="mt-3">
                        <x-v2.input name="password" label="Password" type="password" required />
                    </div>

                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="remember" value="1" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>

                    <div class="mt-4">
                        <x-v2.button type="submit">Masuk</x-v2.button>
                    </div>

                    <div class="mt-3 text-muted">
                        <small>Admin: admin@local.test / 12345678</small><br>
                        <small>Kasir: cashier@local.test / 12345678</small>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection