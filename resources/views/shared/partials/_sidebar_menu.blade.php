@php
    // Mode bisa dipaksa via include: ['variant' => 'admin'|'cashier']
    // Kalau tidak dikasih, auto-detect dari prefix URL.
    $variant = $variant
        ?? (request()->is('admin*') ? 'admin' : (request()->is('cashier*') ? 'cashier' : null));

    $isActive = function ($patterns, $exclude = []) {
        foreach ((array) $exclude as $ex) {
            if (request()->is($ex)) {
                return false;
            }
        }
        foreach ((array) $patterns as $p) {
            if (request()->is($p)) {
                return true;
            }
        }
        return false;
    };

    $adminGroups = [
        [
            'title' => 'MASTER',
            'items' => [
                [
                    'label' => 'Produk (Stok & Daftar)',
                    'url' => url('/admin/products'),
                    'active' => ['admin/products', 'admin/products/*'],
                    'exclude' => ['admin/products/create'],
                ],
                [
                    'label' => 'Tambah Produk',
                    'url' => url('/admin/products/create'),
                    'active' => ['admin/products/create'],
                ],
            ],
        ],
        [
            'title' => 'PEMBELIAN',
            'items' => [
                [
                    'label' => 'Invoice Pembelian',
                    'url' => url('/admin/purchases'),
                    'active' => ['admin/purchases', 'admin/purchases/*'],
                    'exclude' => ['admin/purchases/create'],
                ],
                [
                    'label' => 'Input Pembelian',
                    'url' => url('/admin/purchases/create'),
                    'active' => ['admin/purchases/create'],
                ],
            ],
        ],
        [
            'title' => 'SDM',
            'items' => [
                [
                    'label' => 'Karyawan',
                    'url' => url('/admin/employees'),
                    // loans create ada di /admin/employees/{id}/loans/create -> tetap dianggap bagian karyawan
                    'active' => ['admin/employees', 'admin/employees/*'],
                    'exclude' => ['admin/employees/create'],
                ],
                [
                    'label' => 'Tambah Karyawan',
                    'url' => url('/admin/employees/create'),
                    'active' => ['admin/employees/create'],
                ],
            ],
        ],
        [
            'title' => 'PENGELUARAN',
            'items' => [
                [
                    'label' => 'Pengeluaran',
                    'url' => url('/admin/expenses'),
                    'active' => ['admin/expenses', 'admin/expenses/*'],
                    'exclude' => ['admin/expenses/create'],
                ],
                [
                    'label' => 'Tambah Pengeluaran',
                    'url' => url('/admin/expenses/create'),
                    'active' => ['admin/expenses/create'],
                ],
            ],
        ],
        [
            'title' => 'PAYROLL',
            'items' => [
                [
                    'label' => 'Periode Payroll',
                    'url' => url('/admin/payroll'),
                    'active' => ['admin/payroll', 'admin/payroll/*'],
                    'exclude' => ['admin/payroll/create'],
                ],
                [
                    'label' => 'Buat Periode Payroll',
                    'url' => url('/admin/payroll/create'),
                    'active' => ['admin/payroll/create'],
                ],
            ],
        ],
        [
            'title' => 'LAPORAN',
            'items' => [
                [
                    'label' => 'Laporan Penjualan',
                    'url' => url('/admin/reports/sales'),
                    'active' => ['admin/reports/sales', 'admin/reports/sales/*'],
                ],
                [
                    'label' => 'Laporan Pembelian',
                    'url' => url('/admin/reports/purchasing'),
                    'active' => ['admin/reports/purchasing', 'admin/reports/purchasing/*'],
                ],
                [
                    'label' => 'Laporan Stok',
                    'url' => url('/admin/reports/stock'),
                    'active' => ['admin/reports/stock', 'admin/reports/stock/*'],
                ],
                [
                    'label' => 'Laporan Profit',
                    'url' => url('/admin/reports/profit'),
                    'active' => ['admin/reports/profit', 'admin/reports/profit/*'],
                ],
            ],
        ],
        [
            'title' => 'AUDIT',
            'items' => [
                [
                    'label' => 'Audit Logs',
                    'url' => url('/admin/audit-logs'),
                    'active' => ['admin/audit-logs', 'admin/audit-logs/*'],
                ],
            ],
        ],
    ];

    $cashierGroups = [
        [
            'title' => 'KASIR',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'url' => url('/cashier/dashboard'),
                    'active' => ['cashier/dashboard'],
                ],
                [
                    'label' => 'Transaksi Hari Ini',
                    'url' => url('/cashier/transactions/today'),
                    'active' => ['cashier/transactions/today'],
                ],
                [
                    'label' => 'Cari Produk',
                    'url' => url('/cashier/products/search'),
                    'active' => ['cashier/products/search'],
                ],
            ],
        ],
    ];

    $groups = $variant === 'admin' ? $adminGroups : ($variant === 'cashier' ? $cashierGroups : []);
@endphp

@if (!empty($groups))
    <aside style="width:260px;border-right:1px solid #e5e7eb;min-height:100vh;">
        <div style="padding:14px 16px;border-bottom:1px solid #e5e7eb;">
            <div style="font-weight:700;">APP KASIR</div>
            <div style="font-size:12px;opacity:.7;margin-top:2px;">
                {{ $variant === 'admin' ? 'Admin' : 'Cashier' }}
            </div>
        </div>

        <nav style="padding:10px 8px;">
            @foreach ($groups as $group)
                <div style="padding:10px 10px 6px;font-size:11px;letter-spacing:.06em;opacity:.65;">
                    {{ $group['title'] }}
                </div>

                <ul style="list-style:none;margin:0;padding:0;">
                    @foreach ($group['items'] as $item)
                        @php
                            $active = $isActive($item['active'] ?? [], $item['exclude'] ?? []);
                        @endphp
                        <li style="margin:2px 0;">
                            <a href="{{ $item['url'] }}"
                               style="
                                   display:block;
                                   padding:9px 10px;
                                   border-radius:8px;
                                   text-decoration:none;
                                   color:inherit;
                                   {{ $active ? 'background:#f3f4f6;font-weight:600;' : 'background:transparent;' }}
                               ">
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    </aside>
@endif