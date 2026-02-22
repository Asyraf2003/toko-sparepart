@php
    $activeToday = request()->is('cashier/transactions/today');
    $activeTx = request()->is('cashier/transactions*'); // show, work-order, dll
@endphp

<li class="sidebar-item {{ request()->is('cashier/dashboard') ? 'active' : '' }}">
    <a href="{{ url('/cashier/dashboard') }}" class="sidebar-link">
        <i class="bi bi-grid-fill"></i>
        <span>Dashboard</span>
    </a>
</li>

<li class="sidebar-item {{ $activeTx ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-receipt-cutoff"></i>
        <span>Transaksi</span>
    </a>

    <ul class="submenu {{ $activeTx ? 'active' : '' }}">
        <li class="submenu-item {{ $activeToday ? 'active' : '' }}">
            <a href="{{ url('/cashier/transactions/today') }}" class="submenu-link">Hari Ini</a>
        </li>
    </ul>
</li>

<li class="sidebar-item">
    <a href="{{ url('/logout') }}"
       class="sidebar-link"
       onclick="event.preventDefault(); document.getElementById('logout-form-sidebar').submit();">
        <i class="bi bi-box-arrow-right"></i>
        <span>Logout</span>
    </a>

    <form id="logout-form-sidebar" method="post" action="{{ url('/logout') }}" class="d-none">
        @csrf
    </form>
</li>