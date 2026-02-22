@php
    $initial = (string) request()->query('pq', '');
    $initialTrim = trim($initial);
    $canServerSearch = mb_strlen($initialTrim) >= 2;

    $rows = $productRows ?? collect();
    $txId = (int) ($tx->id ?? 0);
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Cari Sparepart</h4>
    </div>

    <div class="card-body">
        {{-- Fallback NON-JS: submit GET ke halaman transaksi yang sama (pakai pq) --}}
        <form method="get"
              action="{{ url('/cashier/transactions/'.$txId) }}"
              class="row g-2 align-items-end"
              id="product_search_form">
            <div class="col-12 col-md-10">
                <label class="form-label">Cari (SKU/Nama)</label>
                <input id="product_search_pq"
                       name="pq"
                       type="text"
                       class="form-control"
                       value="{{ $initial }}"
                       placeholder="Ketik SKU / Nama...">
            </div>

            <div class="col-6 col-md-1">
                <button id="product_search_submit" type="submit" class="btn btn-primary w-100">
                    Cari
                </button>
            </div>

            <div class="col-6 col-md-1">
                <a id="product_search_reset"
                   href="{{ url('/cashier/transactions/'.$txId) }}"
                   class="btn btn-light w-100">
                    Reset
                </a>
            </div>
        </form>

        <div class="mt-3 text-muted" id="product_search_hint">
            @if (!$canServerSearch)
                Minimal 2 karakter untuk mencari.
            @else
                Hasil: {{ $rows->count() }} item
            @endif
        </div>

        <div class="table-responsive mt-2">
            <table class="table table-hover table-lg mb-0">
                <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama</th>
                    <th>Harga</th>
                    <th>Stok</th>
                    <th>Dipakai</th>
                    <th>Tersedia</th>
                    <th>Aksi</th>
                </tr>
                </thead>
                <tbody id="product_search_rows">
                    @include('cashier.products.partials._rows', [
                        'rows' => $rows,
                        'canSearch' => $canServerSearch,
                        'txId' => $txId,
                    ])
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const txId = {{ (int) $txId }};
    const form = document.getElementById('product_search_form');
    const input = document.getElementById('product_search_pq');
    const tbody = document.getElementById('product_search_rows');
    const hint = document.getElementById('product_search_hint');

    let timer = null;

    function setHint(text) {
        if (hint) hint.textContent = text;
    }

    function setUrlPQ(val) {
        const u = new URL(window.location.href);
        if (val && val.trim() !== '') u.searchParams.set('pq', val.trim());
        else u.searchParams.delete('pq');
        window.history.replaceState({}, '', u.toString());
    }

    async function fetchRowsHTML(q) {
        const url = `/cashier/products/search?pq=${encodeURIComponent(q)}&tx_id=${encodeURIComponent(String(txId))}&fragment=rows`;
        const res = await fetch(url, { headers: { 'Accept': 'text/html' } });
        if (!res.ok) throw new Error('fetch rows failed');

        const html = await res.text();
        const count = res.headers.get('X-Items-Count');
        return { html, count };
    }

    async function searchNow() {
        const q = (input.value || '').trim();
        setUrlPQ(q);

        if (q.length < 2) {
            setHint('Minimal 2 karakter untuk mencari.');
            tbody.innerHTML = '<tr><td colspan="7" class="text-muted">Belum ada hasil.</td></tr>';
            return;
        }

        try {
            setHint('Mencari...');
            const out = await fetchRowsHTML(q);
            tbody.innerHTML = out.html;
            setHint(`Hasil: ${out.count ?? '-'} item`);
        } catch (e) {
            setHint('Gagal mengambil data.');
        }
    }

    function debounceSearch() {
        clearTimeout(timer);
        timer = setTimeout(searchNow, 250);
    }

    // Progressive enhancement:
    // - Dengan JS: submit form menjadi fetch HTML (tanpa reload)
    // - Tanpa JS: form submit normal tetap jalan
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            await searchNow();
        } catch (_) {
            // fallback terakhir: submit normal
            form.submit();
        }
    });

    input.addEventListener('input', debounceSearch);

    // Add part: fallback-nya sudah <form method="post"> di rows partial.
    // Dengan JS, kita intercept submit supaya UX cepat, lalu reload untuk sinkron summary/lines.
    tbody.addEventListener('submit', async (e) => {
        const f = e.target;
        if (!f || !f.matches('form[data-add-part-form="1"]')) return;

        e.preventDefault();

        const fd = new FormData(f);
        const body = new URLSearchParams(fd);

        try {
            const res = await fetch(f.action, {
                method: 'POST',
                headers: {
                    'Accept': 'text/html',
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                },
                body: body.toString(),
            });

            if (res.ok) {
                if (window.APPKASIR_TX && typeof window.APPKASIR_TX.refresh === 'function') {
                    await window.APPKASIR_TX.refresh();
                } else {
                    window.location.reload();
                    return;
                }

                // optional: refresh hasil pencarian agar stok/reserved ikut update
                await searchNow();
                return;
            }

            alert('Gagal menambah sparepart. Cek validasi/server.');
        } catch (_) {
            alert('Gagal menambah sparepart (network).');
        }
    });
})();
</script>
@endpush