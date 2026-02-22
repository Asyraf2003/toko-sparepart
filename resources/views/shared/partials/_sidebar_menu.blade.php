@php
    /**
     * Shared Sidebar Menu (single source of truth)
     *
     * Params:
     * - $variant: 'admin'|'cashier' (optional; auto-detect)
     * - $render : 'mazer'|'inline'  (optional; default 'mazer')
     *
     * render=mazer  => output <li>...</li> (untuk layout Mazer / shared.layouts.app)
     * render=inline => output <aside>...</aside> (untuk admin legacy yang belum migrasi)
     */

    $variant = $variant
        ?? (request()->is('admin*') ? 'admin' : (request()->is('cashier*') ? 'cashier' : null));

    $render = $render ?? 'mazer';

    $isActive = function ($patterns, $exclude = []) : bool {
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

    // ===== MENU CONFIG (1 tempat) =====

    $adminGroups = [
        [
            'title' => 'DASHBOARD',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'url' => url('/admin'),
                    'active' => ['admin'],
                ],
            ],
        ],
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
                    'label' => 'Karyawan (Daftar)',
                    'url' => url('/admin/employees'),
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
                    'label' => 'Pengeluaran (Daftar)',
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
                    'label' => 'Cetak Penjualan (PDF)',
                    'url' => url('/admin/reports/sales/pdf'),
                    'active' => ['admin/reports/sales/pdf'],
                    'target' => '_blank',
                    'rel' => 'noopener',
                ],
                [
                    'label' => 'Laporan Pembelian',
                    'url' => url('/admin/reports/purchasing'),
                    'active' => ['admin/reports/purchasing', 'admin/reports/purchasing/*'],
                ],
                [
                    'label' => 'Cetak Pembelian (PDF)',
                    'url' => url('/admin/reports/purchasing/pdf'),
                    'active' => ['admin/reports/purchasing/pdf'],
                    'target' => '_blank',
                    'rel' => 'noopener',
                ],
                [
                    'label' => 'Laporan Stok',
                    'url' => url('/admin/reports/stock'),
                    'active' => ['admin/reports/stock', 'admin/reports/stock/*'],
                ],
                [
                    'label' => 'Cetak Stok (PDF)',
                    'url' => url('/admin/reports/stock/pdf'),
                    'active' => ['admin/reports/stock/pdf'],
                    'target' => '_blank',
                    'rel' => 'noopener',
                ],
                [
                    'label' => 'Laporan Profit',
                    'url' => url('/admin/reports/profit'),
                    'active' => ['admin/reports/profit', 'admin/reports/profit/*'],
                ],
                [
                    'label' => 'Cetak Profit (PDF)',
                    'url' => url('/admin/reports/profit/pdf'),
                    'active' => ['admin/reports/profit/pdf'],
                    'target' => '_blank',
                    'rel' => 'noopener',
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
            'title' => null,
            'items' => [
                [
                    'label' => 'Dashboard',
                    'url' => url('/cashier/dashboard'),
                    'icon' => 'bi bi-grid-fill',
                    'active' => ['cashier/dashboard'],
                ],
                [
                    'label' => 'Transaksi',
                    'url' => '#',
                    'icon' => 'bi bi-receipt-cutoff',
                    'active' => ['cashier/transactions*'],
                    'children' => [
                        [
                            'label' => 'Hari Ini',
                            'url' => url('/cashier/transactions/today'),
                            'active' => ['cashier/transactions/today'],
                        ],
                    ],
                ],
                [
                    'kind' => 'logout',
                    'label' => 'Logout',
                    'url' => url('/logout'),
                    'icon' => 'bi bi-box-arrow-right',
                ],
            ],
        ],
    ];

    $groups = $variant === 'admin' ? $adminGroups : ($variant === 'cashier' ? $cashierGroups : []);
@endphp

@if (empty($groups))
    {{-- no-op --}}
@elseif ($render === 'inline')
    @php
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
            @foreach ($groups as $group)
                @if (!empty($group['title']))
                    <div style="{{ $groupTitleStyle }}">{{ $group['title'] }}</div>
                @endif

                <ul style="{{ $sectionStyle }}">
                    @foreach (($group['items'] ?? []) as $item)
                        @php
                            if (!empty($item['children'])) continue;
                            if (($item['kind'] ?? 'link') !== 'link') continue;
                            $active = $isActive($item['active'] ?? [], $item['exclude'] ?? []);
                        @endphp

                        <li style="{{ $liStyle }}">
                            <a href="{{ $item['url'] }}"
                               style="{{ $active ? $linkActiveStyle : $linkStyle }}"
                               @if (!empty($item['target'])) target="{{ $item['target'] }}" @endif
                               @if (!empty($item['rel'])) rel="{{ $item['rel'] }}" @endif>
                                {{ $item['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    </aside>
@else
    {{-- Mazer renderer => output LI only --}}
    @foreach ($groups as $group)
        @if (!empty($group['title']))
            <li class="sidebar-title">{{ $group['title'] }}</li>
        @endif

        @foreach (($group['items'] ?? []) as $item)
            @php
                $kind = $item['kind'] ?? 'link';
                $hasChildren = !empty($item['children']);
                $active = $isActive($item['active'] ?? [], $item['exclude'] ?? []);
            @endphp

            @if ($kind === 'logout')
                <li class="sidebar-item">
                    <a href="{{ $item['url'] }}"
                       class="sidebar-link"
                       onclick="event.preventDefault(); document.getElementById('logout-form-sidebar').submit();">
                        @if (!empty($item['icon'])) <i class="{{ $item['icon'] }}"></i> @endif
                        <span>{{ $item['label'] }}</span>
                    </a>

                    <form id="logout-form-sidebar" method="post" action="{{ $item['url'] }}" class="d-none">
                        @csrf
                    </form>
                </li>
            @elseif ($hasChildren)
                <li class="sidebar-item {{ $active ? 'active' : '' }} has-sub">
                    <a href="#" class="sidebar-link">
                        @if (!empty($item['icon'])) <i class="{{ $item['icon'] }}"></i> @endif
                        <span>{{ $item['label'] }}</span>
                    </a>

                    <ul class="submenu {{ $active ? 'active' : '' }}">
                        @foreach (($item['children'] ?? []) as $child)
                            @php $childActive = $isActive($child['active'] ?? [], $child['exclude'] ?? []); @endphp
                            <li class="submenu-item {{ $childActive ? 'active' : '' }}">
                                <a href="{{ $child['url'] }}" class="submenu-link">{{ $child['label'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </li>
            @else
                <li class="sidebar-item {{ $active ? 'active' : '' }}">
                    <a href="{{ $item['url'] }}"
                       class="sidebar-link"
                       @if (!empty($item['target'])) target="{{ $item['target'] }}" @endif
                       @if (!empty($item['rel'])) rel="{{ $item['rel'] }}" @endif>
                        @if (!empty($item['icon'])) <i class="{{ $item['icon'] }}"></i> @endif
                        <span>{{ $item['label'] }}</span>
                    </a>
                </li>
            @endif
        @endforeach
    @endforeach
@endif