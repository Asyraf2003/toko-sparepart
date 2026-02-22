@extends('shared.layouts.base')

@php
    $variant = request()->is('admin*') ? 'admin' : (request()->is('cashier*') ? 'cashier' : null);
    $homeUrl = $variant === 'admin'
        ? url('/admin')
        : ($variant === 'cashier' ? url('/cashier') : url('/'));
@endphp

@section('body')
    <div id="app">
        <div id="sidebar">
            <div class="sidebar-wrapper active">
                <div class="sidebar-header position-relative">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="logo">
                            <a href="{{ $homeUrl }}">
                                <img src="{{ asset('assets/compiled/svg/logo.svg') }}" alt="Logo">
                            </a>
                        </div>

                        <div class="theme-toggle d-flex gap-2 align-items-center mt-2">
                            <div class="form-check form-switch fs-6">
                                <input class="form-check-input me-0" type="checkbox" id="toggle-dark" style="cursor: pointer">
                                <label class="form-check-label"></label>
                            </div>
                        </div>

                        <div class="sidebar-toggler x">
                            <a href="#" class="sidebar-hide d-xl-none d-block"><i class="bi bi-x bi-middle"></i></a>
                        </div>
                    </div>
                </div>

                <div class="sidebar-menu">
                    <ul class="menu">
                        <li class="sidebar-title">Menu</li>
                        @include('shared.partials._sidebar_menu', [
                            'variant' => $variant,
                            'render' => 'mazer',
                        ])
                    </ul>
                </div>
            </div>
        </div>

        <div id="main">
            <header class="mb-3">
                <a href="#" class="burger-btn d-block d-xl-none">
                    <i class="bi bi-justify fs-3"></i>
                </a>
            </header>

            @yield('page_heading')

            <div class="page-content">
                @include('shared.partials._flash')
                @yield('content')
            </div>

            @include('shared.partials._footer')
        </div>
    </div>
@endsection