@php
    /**
     * Sidebar Menu Admin (Pegangan)
     * - Semua link sesuai route admin (GET)
     * - Active state pakai request()->is()
     */
    $isActive = function (array $patterns, array $exclude = []) : bool {
        foreach ($exclude as $ex) {
            if (request()->is($ex)) {
                return false;
            }
        }
        foreach ($patterns as $p) {
            if (request()->is($p)) {
                return true;
            }
        }
        return false;
    };

    $linkStyle = 'display:block;padding:10px 12px;border-radius:10px;text-decoration:none;color:inherit;';
    $linkActiveStyle = $linkStyle.'background:#f3f4f6;font-weight:700;';
    $groupTitleStyle = 'padding:14px 12px 6px;font-size:11px;letter-spacing:.08em;opacity:.65;';
    $sectionStyle = 'margin:0;padding:0;list-style:none;';
    $liStyle = 'margin:3px 0;';
@endphp

<aside style="width:270px;min-height:100vh;border-right:1px solid #e5e7eb;background:#fff;">
    <div style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
        <div style="font-weight:800;">APP KASIR</div>
        <div style="font-size:12px;opacity:.7;margin-top:2px;">Menu Admin</div>
    </div>

    <nav style="padding:10px 10px 14px;">
        {{-- MASTER --}}
        <div style="{{ $groupTitleStyle }}">MASTER</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/products', 'admin/products/*'], ['admin/products/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/products') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Produk (Stok & Daftar)
                </a>
            </li>

            @php $active = $isActive(['admin/products/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/products/create') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Tambah Produk
                </a>
            </li>
        </ul>

        {{-- PEMBELIAN --}}
        <div style="{{ $groupTitleStyle }}">PEMBELIAN</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/purchases', 'admin/purchases/*'], ['admin/purchases/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/purchases') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Invoice Pembelian
                </a>
            </li>

            @php $active = $isActive(['admin/purchases/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/purchases/create') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Input Pembelian
                </a>
            </li>
        </ul>

        {{-- SDM --}}
        <div style="{{ $groupTitleStyle }}">SDM</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/employees', 'admin/employees/*'], ['admin/employees/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/employees') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Karyawan (Daftar)
                </a>
            </li>

            @php $active = $isActive(['admin/employees/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/employees/create') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Tambah Karyawan
                </a>
            </li>

            {{-- Catatan: Halaman pinjaman karyawan ada route dinamis:
                 /admin/employees/{employeeId}/loans/create
                 Ini biasanya diakses dari halaman detail/daftar karyawan, jadi tidak dibuat link langsung di menu.
            --}}
        </ul>

        {{-- PENGELUARAN --}}
        <div style="{{ $groupTitleStyle }}">PENGELUARAN</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/expenses', 'admin/expenses/*'], ['admin/expenses/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/expenses') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Pengeluaran (Daftar)
                </a>
            </li>

            @php $active = $isActive(['admin/expenses/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/expenses/create') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Tambah Pengeluaran
                </a>
            </li>
        </ul>

        {{-- PAYROLL --}}
        <div style="{{ $groupTitleStyle }}">PAYROLL</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/payroll', 'admin/payroll/*'], ['admin/payroll/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/payroll') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Periode Payroll
                </a>
            </li>

            @php $active = $isActive(['admin/payroll/create']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/payroll/create') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Buat Periode Payroll
                </a>
            </li>
        </ul>

        {{-- LAPORAN --}}
        <div style="{{ $groupTitleStyle }}">LAPORAN</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/reports/sales', 'admin/reports/sales/*']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/sales') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Laporan Penjualan
                </a>
            </li>
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/sales/pdf') }}" target="_blank" rel="noopener"
                   style="{{ $isActive(['admin/reports/sales/pdf']) ? $linkActiveStyle : $linkStyle }}">
                    Cetak Penjualan (PDF)
                </a>
            </li>

            @php $active = $isActive(['admin/reports/purchasing', 'admin/reports/purchasing/*']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/purchasing') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Laporan Pembelian
                </a>
            </li>
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/purchasing/pdf') }}" target="_blank" rel="noopener"
                   style="{{ $isActive(['admin/reports/purchasing/pdf']) ? $linkActiveStyle : $linkStyle }}">
                    Cetak Pembelian (PDF)
                </a>
            </li>

            @php $active = $isActive(['admin/reports/stock', 'admin/reports/stock/*']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/stock') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Laporan Stok
                </a>
            </li>
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/stock/pdf') }}" target="_blank" rel="noopener"
                   style="{{ $isActive(['admin/reports/stock/pdf']) ? $linkActiveStyle : $linkStyle }}">
                    Cetak Stok (PDF)
                </a>
            </li>

            @php $active = $isActive(['admin/reports/profit', 'admin/reports/profit/*']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/profit') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Laporan Profit
                </a>
            </li>
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/reports/profit/pdf') }}" target="_blank" rel="noopener"
                   style="{{ $isActive(['admin/reports/profit/pdf']) ? $linkActiveStyle : $linkStyle }}">
                    Cetak Profit (PDF)
                </a>
            </li>
        </ul>

        {{-- AUDIT --}}
        <div style="{{ $groupTitleStyle }}">AUDIT</div>
        <ul style="{{ $sectionStyle }}">
            @php $active = $isActive(['admin/audit-logs', 'admin/audit-logs/*']); @endphp
            <li style="{{ $liStyle }}">
                <a href="{{ url('/admin/audit-logs') }}" style="{{ $active ? $linkActiveStyle : $linkStyle }}">
                    Audit Logs
                </a>
            </li>
        </ul>

        <div style="padding:14px 12px 0;opacity:.6;font-size:12px;">
            <div style="border-top:1px dashed #e5e7eb;padding-top:10px;">
                <a href="{{ url('/admin') }}" style="text-decoration:none;opacity:.75;">Ke Beranda Admin</a>
            </div>
        </div>
    </nav>
</aside>