@php
    $activeProducts = request()->is('admin/products*');
    $activePurchases = request()->is('admin/purchases*');
    $activeEmployees = request()->is('admin/employees*');
    $activeExpenses = request()->is('admin/expenses*');
    $activePayroll = request()->is('admin/payroll*');

    $activeReportsSales = request()->is('admin/reports/sales*');
    $activeReportsPurchasing = request()->is('admin/reports/purchasing*');
    $activeReportsStock = request()->is('admin/reports/stock*');
    $activeReportsProfit = request()->is('admin/reports/profit*');
    $activeReports = $activeReportsSales || $activeReportsPurchasing || $activeReportsStock || $activeReportsProfit;

    $activeAudit = request()->is('admin/audit-logs*');
@endphp

<li class="sidebar-item {{ $activeProducts ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-box-seam"></i>
        <span>Produk</span>
    </a>

    <ul class="submenu {{ $activeProducts ? 'active' : '' }}">
        <li class="submenu-item {{ request()->is('admin/products') ? 'active' : '' }}">
            <a href="{{ url('/admin/products') }}" class="submenu-link">Produk & Stok</a>
        </li>
        <li class="submenu-item {{ request()->is('admin/products/create') ? 'active' : '' }}">
            <a href="{{ url('/admin/products/create') }}" class="submenu-link">Tambah Produk</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activePurchases ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-bag-plus"></i>
        <span>Pembelian</span>
    </a>

    <ul class="submenu {{ $activePurchases ? 'active' : '' }}">
        <li class="submenu-item {{ request()->is('admin/purchases') ? 'active' : '' }}">
            <a href="{{ url('/admin/purchases') }}" class="submenu-link">Daftar Pembelian</a>
        </li>
        <li class="submenu-item {{ request()->is('admin/purchases/create') ? 'active' : '' }}">
            <a href="{{ url('/admin/purchases/create') }}" class="submenu-link">Buat Pembelian</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activeEmployees ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-people"></i>
        <span>Karyawan</span>
    </a>

    <ul class="submenu {{ $activeEmployees ? 'active' : '' }}">
        <li class="submenu-item {{ request()->is('admin/employees') ? 'active' : '' }}">
            <a href="{{ url('/admin/employees') }}" class="submenu-link">Daftar Karyawan</a>
        </li>
        <li class="submenu-item {{ request()->is('admin/employees/create') ? 'active' : '' }}">
            <a href="{{ url('/admin/employees/create') }}" class="submenu-link">Tambah Karyawan</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activeExpenses ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-receipt"></i>
        <span>Pengeluaran</span>
    </a>

    <ul class="submenu {{ $activeExpenses ? 'active' : '' }}">
        <li class="submenu-item {{ request()->is('admin/expenses') ? 'active' : '' }}">
            <a href="{{ url('/admin/expenses') }}" class="submenu-link">Daftar Pengeluaran</a>
        </li>
        <li class="submenu-item {{ request()->is('admin/expenses/create') ? 'active' : '' }}">
            <a href="{{ url('/admin/expenses/create') }}" class="submenu-link">Tambah Pengeluaran</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activePayroll ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-cash-stack"></i>
        <span>Payroll</span>
    </a>

    <ul class="submenu {{ $activePayroll ? 'active' : '' }}">
        <li class="submenu-item {{ request()->is('admin/payroll') ? 'active' : '' }}">
            <a href="{{ url('/admin/payroll') }}" class="submenu-link">Periode Payroll</a>
        </li>
        <li class="submenu-item {{ request()->is('admin/payroll/create') ? 'active' : '' }}">
            <a href="{{ url('/admin/payroll/create') }}" class="submenu-link">Buat Periode</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activeReports ? 'active' : '' }} has-sub">
    <a href="#" class="sidebar-link">
        <i class="bi bi-bar-chart"></i>
        <span>Laporan</span>
    </a>

    <ul class="submenu {{ $activeReports ? 'active' : '' }}">
        <li class="submenu-item {{ $activeReportsSales && request()->is('admin/reports/sales') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/sales') }}" class="submenu-link">Penjualan</a>
        </li>
        <li class="submenu-item {{ $activeReportsSales && request()->is('admin/reports/sales/pdf') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/sales/pdf') }}" class="submenu-link" target="_blank" rel="noopener">Penjualan (PDF)</a>
        </li>

        <li class="submenu-item {{ $activeReportsPurchasing && request()->is('admin/reports/purchasing') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/purchasing') }}" class="submenu-link">Pembelian</a>
        </li>
        <li class="submenu-item {{ $activeReportsPurchasing && request()->is('admin/reports/purchasing/pdf') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/purchasing/pdf') }}" class="submenu-link" target="_blank" rel="noopener">Pembelian (PDF)</a>
        </li>

        <li class="submenu-item {{ $activeReportsStock && request()->is('admin/reports/stock') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/stock') }}" class="submenu-link">Stok</a>
        </li>
        <li class="submenu-item {{ $activeReportsStock && request()->is('admin/reports/stock/pdf') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/stock/pdf') }}" class="submenu-link" target="_blank" rel="noopener">Stok (PDF)</a>
        </li>

        <li class="submenu-item {{ $activeReportsProfit && request()->is('admin/reports/profit') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/profit') }}" class="submenu-link">Laba</a>
        </li>
        <li class="submenu-item {{ $activeReportsProfit && request()->is('admin/reports/profit/pdf') ? 'active' : '' }}">
            <a href="{{ url('/admin/reports/profit/pdf') }}" class="submenu-link" target="_blank" rel="noopener">Laba (PDF)</a>
        </li>
    </ul>
</li>

<li class="sidebar-item {{ $activeAudit ? 'active' : '' }}">
    <a href="{{ url('/admin/audit-logs') }}" class="sidebar-link">
        <i class="bi bi-journal-text"></i>
        <span>Audit Logs</span>
    </a>
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