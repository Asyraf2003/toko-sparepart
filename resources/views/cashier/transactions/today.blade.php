@extends('cashier.layouts.app')

@section('title', 'Kasir - Transaksi Hari Ini')

@section('page_heading')
    <div class="page-heading">
        <h3 class="mb-1">Transaksi Hari Ini</h3>
        <div class="text-muted">({{ $today }})</div>
    </div>
@endsection

@section('content')
    <section class="section">
        <div class="row">

            {{-- KANAN: Aksi + Filter --}}
            <div class="col-12 col-lg-4 order-1 order-lg-2">

                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Aksi Cepat</h4>
                    </div>
                    <div class="card-body d-flex gap-2 flex-wrap">
                        <form method="post" action="{{ url('/cashier/transactions') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-outline-primary">
                                + Buat Nota Baru (DRAFT)
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card mt-3">
                    <div class="card-header">
                        <h4 class="card-title mb-0">Filter</h4>
                    </div>
                    <div class="card-body">
                        <form method="get"
                              action="{{ url('/cashier/transactions/today') }}"
                              class="row g-2 align-items-end"
                              id="today_filter_form">
                            <div class="col-12">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="today_status">
                                    <option value="" @if(($status ?? '') === '') selected @endif>ALL</option>
                                    <option value="DRAFT" @if(($status ?? '') === 'DRAFT') selected @endif>DRAFT</option>
                                    <option value="OPEN" @if(($status ?? '') === 'OPEN') selected @endif>OPEN</option>
                                    <option value="COMPLETED" @if(($status ?? '') === 'COMPLETED') selected @endif>COMPLETED</option>
                                    <option value="VOID" @if(($status ?? '') === 'VOID') selected @endif>VOID</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Cari No</label>
                                <input type="text"
                                       name="q"
                                       value="{{ $q ?? '' }}"
                                       placeholder="INV-..."
                                       class="form-control"
                                       id="today_q">
                            </div>

                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-primary" id="today_apply_btn">Apply</button>
                                <a class="btn btn-light" href="{{ url('/cashier/transactions/today') }}" id="today_reset_link">Reset</a>
                            </div>

                            <div class="col-12">
                                <div class="text-muted" style="font-size: 12px;" id="today_hint"></div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>

            {{-- KIRI: Daftar transaksi (utama) --}}
            <div class="col-12 col-lg-8 order-2 order-lg-1">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">Daftar Transaksi</h4>
                        <div class="text-muted">
                            Total: <span id="today_total">{{ $rows->total() }}</span>
                        </div>
                    </div>

                    <div class="card-body">
                        <div id="today_list">
                            @include('cashier.transactions.partials._today_list', [
                                'rows' => $rows,
                                'hasCustomerName' => $hasCustomerName,
                                'hasVehiclePlate' => $hasVehiclePlate,
                            ])
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
@endsection

@push('scripts')
<script>
(function () {
    const form = document.getElementById('today_filter_form');
    const qInput = document.getElementById('today_q');
    const statusSelect = document.getElementById('today_status');
    const listWrap = document.getElementById('today_list');
    const totalEl = document.getElementById('today_total');
    const hint = document.getElementById('today_hint');
    const applyBtn = document.getElementById('today_apply_btn');

    let timer = null;
    let loading = false;

    function setHint(text) {
        if (hint) hint.textContent = text || '';
    }

    function setLoading(isLoading) {
        loading = isLoading;
        if (applyBtn) applyBtn.disabled = isLoading;
        if (qInput) qInput.disabled = isLoading;
        if (statusSelect) statusSelect.disabled = isLoading;
        setHint(isLoading ? 'Memuat...' : '');
    }

    function normalizeUrl(url) {
        const u = new URL(url, window.location.origin);
        u.searchParams.delete('fragment');
        return u.toString();
    }

    async function load(url, pushState) {
        const pageUrl = normalizeUrl(url);
        const fetchUrl = new URL(pageUrl, window.location.origin);
        fetchUrl.searchParams.set('fragment', '1');

        setLoading(true);

        try {
            const res = await fetch(fetchUrl.toString(), {
                headers: { 'Accept': 'text/html' }
            });

            if (!res.ok) throw new Error('HTTP ' + res.status);

            const html = await res.text();

            const tmp = document.createElement('div');
            tmp.innerHTML = html;

            const root = tmp.querySelector('#today_fragment_root');
            if (!root) throw new Error('Fragment root not found');

            listWrap.innerHTML = root.outerHTML;

            // update total header
            const total = root.getAttribute('data-total');
            if (totalEl && total !== null) totalEl.textContent = total;

            if (pushState) {
                window.history.pushState({}, '', pageUrl);
            }

            setLoading(false);
        } catch (e) {
            setLoading(false);
            // fallback: full reload
            window.location.href = pageUrl;
        }
    }

    // Intercept filter submit
    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const u = new URL(form.action, window.location.origin);
        const fd = new FormData(form);

        // only append non-empty values
        for (const [k, v] of fd.entries()) {
            const val = String(v || '').trim();
            if (val !== '') u.searchParams.set(k, val);
        }

        // reset page to 1 on new filter
        u.searchParams.delete('page');

        load(u.toString(), true);
    });

    // Auto-search JS (debounce) — tetap fallback ada (submit manual)
    qInput.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            // trigger submit programmatically
            const ev = new Event('submit', { cancelable: true });
            form.dispatchEvent(ev);
        }, 300);
    });

    statusSelect.addEventListener('change', function () {
        const ev = new Event('submit', { cancelable: true });
        form.dispatchEvent(ev);
    });

    // Intercept pagination clicks (event delegation)
    listWrap.addEventListener('click', function (e) {
        const a = e.target.closest('a.page-link');
        if (!a) return;

        const li = a.closest('.page-item');
        if (li && (li.classList.contains('disabled') || li.classList.contains('active'))) {
            e.preventDefault();
            return;
        }

        e.preventDefault();
        load(a.href, true);
    });

    // Handle back/forward
    window.addEventListener('popstate', function () {
        if (loading) return;
        load(window.location.href, false);
    });
})();
</script>
@endpush