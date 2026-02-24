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
                    'icon' => 'bi bi-speedometer2',
                    'active' => ['admin'],
                ],
            ],
        ],
        [
            'title' => 'MASTER',
            'items' => [
                [
                    'label' => 'Produk',
                    'url' => url('/admin/products'),
                    'icon' => 'bi bi-box-seam',
                    'active' => ['admin/products', 'admin/products/*'],
                    'exclude' => ['admin/products/create'],
                ],
            ],
        ],
        [
            'title' => 'PEMBELIAN',
            'items' => [
                [
                    'label' => 'Pembelian Stok Barang',
                    'url' => url('/admin/purchases'),
                    'icon' => 'bi bi-receipt',
                    'active' => ['admin/purchases', 'admin/purchases/*'],
                    'exclude' => ['admin/purchases/create'],
                ],
            ],
        ],
        [
            'title' => 'SDM',
            'items' => [
                [
                    'label' => 'Karyawan',
                    'url' => url('/admin/employees'),
                    'icon' => 'bi bi-people',
                    'active' => ['admin/employees', 'admin/employees/*'],
                    'exclude' => ['admin/employees/create'],
                ],
            ],
        ],
        [
            'title' => 'PENGELUARAN',
            'items' => [
                [
                    'label' => 'Biaya Operasional',
                    'url' => url('/admin/expenses'),
                    'icon' => 'bi bi-cash-coin',
                    'active' => ['admin/expenses', 'admin/expenses/*'],
                    'exclude' => ['admin/expenses/create'],
                ],
            ],
        ],
        [
            'title' => 'GAJI',
            'items' => [
                [
                    'label' => 'Periode Gaji Karyawan',
                    'url' => url('/admin/payroll'),
                    'icon' => 'bi bi-calendar2-week',
                    'active' => ['admin/payroll', 'admin/payroll/*'],
                    'exclude' => ['admin/payroll/create'],
                ],
            ],
        ],
        [
            'title' => 'LAPORAN',
            'items' => [
                [
                    'label' => 'Laporan Penjualan',
                    'url' => url('/admin/reports/sales'),
                    'icon' => 'bi bi-graph-up',
                    'active' => ['admin/reports/sales', 'admin/reports/sales/*'],
                ],
                [
                    'label' => 'Laporan Pembelian',
                    'url' => url('/admin/reports/purchasing'),
                    'icon' => 'bi bi-cart-check',
                    'active' => ['admin/reports/purchasing', 'admin/reports/purchasing/*'],
                ],
                [
                    'label' => 'Laporan Stok',
                    'url' => url('/admin/reports/stock'),
                    'icon' => 'bi bi-boxes',
                    'active' => ['admin/reports/stock', 'admin/reports/stock/*'],
                ],
                [
                    'label' => 'Laporan Keuntungan',
                    'url' => url('/admin/reports/profit'),
                    'icon' => 'bi bi-piggy-bank',
                    'active' => ['admin/reports/profit', 'admin/reports/profit/*'],
                ],
            ],
        ],

        // ✅ ADD: TELEGRAM OPS
        [
            'title' => 'TELEGRAM',
            'items' => [
                [
                    'label' => 'Telegram Bot',
                    'url' => url('/admin/telegram'),
                    'icon' => 'bi bi-telegram',
                    'active' => ['admin/telegram'],
                ],
                [
                    'label' => 'Bukti Bayar (Telegram)',
                    'url' => url('/admin/telegram/payment-proofs'),
                    'icon' => 'bi bi-file-earmark-check',
                    'active' => ['admin/telegram/payment-proofs', 'admin/telegram/payment-proofs/*'],
                ],
            ],
        ],

        [
            'title' => 'AUDIT',
            'items' => [
                [
                    'label' => 'Catatan Audit',
                    'url' => url('/admin/audit-logs'),
                    'icon' => 'bi bi-shield-check',
                    'active' => ['admin/audit-logs', 'admin/audit-logs/*'],
                ],
            ],
        ],
        [
            'title' => 'AKUN',
            'items' => [
                [
                    'kind' => 'logout',
                    'label' => 'Keluar',
                    'url' => url('/logout'),
                    'icon' => 'bi bi-box-arrow-right',
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
                            'icon' => 'bi bi-calendar-day',
                            'active' => ['cashier/transactions/today'],
                        ],
                    ],
                ],
                [
                    'kind' => 'logout',
                    'label' => 'Keluar',
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
        $linkStyle = 'display:flex;align-items:center;gap:8px;padding:10px 12px;border-radius:10px;text-decoration:none;color:inherit;';
        $linkActiveStyle = $linkStyle.'background:#f3f4f6;font-weight:700;';
        $groupTitleStyle = 'padding:14px 12px 6px;font-size:11px;letter-spacing:.08em;opacity:.65;';
        $sectionStyle = 'margin:0;padding:0;list-style:none;';
        $liStyle = 'margin:3px 0;';
        $iconStyle = 'opacity:.9;';
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
                            // inline admin legacy: tetap skip children (karena butuh UI collapsible terpisah)
                            if (!empty($item['children'])) continue;

                            if (($item['kind'] ?? 'link') === 'logout') {
                                $formId = 'logout-form-sidebar-'.$variant.'-inline';
                                echo '<li style="'.$liStyle.'">';
                                echo '<a href="'.$item['url'].'" style="'.$linkStyle.'" onclick="event.preventDefault(); document.getElementById(\''.$formId.'\').submit();">';
                                if (!empty($item['icon'])) {
                                    echo '<i class="'.$item['icon'].'" style="'.$iconStyle.'"></i>';
                                }
                                echo '<span>'.e($item['label']).'</span>';
                                echo '</a>';
                                echo '<form id="'.$formId.'" method="post" action="'.$item['url'].'" style="display:none;">';
                                echo csrf_field();
                                echo '</form>';
                                echo '</li>';
                                continue;
                            }

                            if (($item['kind'] ?? 'link') !== 'link') {
                                continue;
                            }
                            $active = $isActive($item['active'] ?? [], $item['exclude'] ?? []);
                        @endphp

                        <li style="{{ $liStyle }}">
                            <a href="{{ $item['url'] }}"
                               style="{{ $active ? $linkActiveStyle : $linkStyle }}"
                               @if (!empty($item['target'])) target="{{ $item['target'] }}" @endif
                               @if (!empty($item['rel'])) rel="{{ $item['rel'] }}" @endif>
                                @if (!empty($item['icon']))
                                    <i class="{{ $item['icon'] }}" style="{{ $iconStyle }}"></i>
                                @endif
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>
    </aside>
@else
    {{-- Mazer renderer => output LI only --}}
    @php
        $logoutFormId = 'logout-form-sidebar-'.$variant;
    @endphp

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
                       onclick="event.preventDefault(); document.getElementById('{{ $logoutFormId }}').submit();">
                        @if (!empty($item['icon'])) <i class="{{ $item['icon'] }}"></i> @endif
                        <span>{{ $item['label'] }}</span>
                    </a>

                    <form id="{{ $logoutFormId }}" method="post" action="{{ $item['url'] }}" class="d-none">
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
                                <a href="{{ $child['url'] }}" class="submenu-link">
                                    @if (!empty($child['icon'])) <i class="{{ $child['icon'] }}"></i> @endif
                                    <span>{{ $child['label'] }}</span>
                                </a>
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