@php
    $initial = (string) request()->query('pq', '');
@endphp

<div class="card mt-3">
    <div class="card-header">
        <h4>Cari Sparepart</h4>
    </div>

    <div class="card-body">
        <div class="row g-2 align-items-end">
            <div class="col-12 col-md-11">
                <label class="form-label">Cari (SKU/Nama)</label>
                <input id="product_search_q"
                       type="text"
                       class="form-control"
                       value="{{ $initial }}"
                       placeholder="Ketik SKU / Nama...">
            </div>

            <div class="col-12 col-md-1">
                <button id="product_search_clear" type="button" class="btn btn-light w-100">
                    Reset
                </button>
            </div>
        </div>

        <div class="mt-3" id="product_search_hint" class="text-muted">
            Minimal 2 karakter untuk mencari.
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
                <tr>
                    <td colspan="7" class="text-muted">Belum ada hasil.</td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
(function () {
    const txId = {{ (int) $tx->id }};
    const input = document.getElementById('product_search_q');
    const btnClear = document.getElementById('product_search_clear');
    const tbody = document.getElementById('product_search_rows');
    const hint = document.getElementById('product_search_hint');

    let timer = null;

    function setUrlPQ(val) {
        const u = new URL(window.location.href);
        if (val && val.trim() !== '') u.searchParams.set('pq', val.trim());
        else u.searchParams.delete('pq');
        window.history.replaceState({}, '', u.toString());
    }

    function esc(s) {
        return String(s)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function renderEmpty(msg) {
        tbody.innerHTML = '<tr><td colspan="7" class="text-muted">' + esc(msg) + '</td></tr>';
    }

    function render(items) {
        if (!items || items.length === 0) {
            renderEmpty('Tidak ada hasil.');
            return;
        }

        tbody.innerHTML = items.map((p) => {
            return `
<tr>
  <td>${esc(p.sku)}</td>
  <td>${esc(p.name)}</td>
  <td>${esc(p.sell_price_current)}</td>
  <td>${esc(p.on_hand_qty)}</td>
  <td>${esc(p.reserved_qty)}</td>
  <td>${esc(p.available_qty)}</td>
  <td style="min-width: 160px;">
    <button type="button"
            class="btn btn-sm icon icon-left btn-success w-100 d-flex align-items-center justify-content-center"
            data-add="${esc(p.id)}"
            data-sku="${esc(p.sku)}">
        <i data-feather="plus-circle"></i>
        Tambah
    </button>
  </td>
</tr>`;
        }).join('');
    }

    async function searchNow() {
        const q = (input.value || '').trim();
        setUrlPQ(q);

        if (q.length < 2) {
            hint.textContent = 'Minimal 2 karakter untuk mencari.';
            renderEmpty('Belum ada hasil.');
            return;
        }

        hint.textContent = 'Mencari...';

        const url = `/cashier/products/search?q=${encodeURIComponent(q)}`;
        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

        if (!res.ok) {
            hint.textContent = 'Gagal mengambil data.';
            renderEmpty('Gagal mencari.');
            return;
        }

        const data = await res.json();
        hint.textContent = `Hasil: ${(data.items || []).length} item`;
        render(data.items || []);

        if (window.feather && typeof window.feather.replace === 'function') {
            window.feather.replace();
        }
    }

    function debounceSearch() {
        clearTimeout(timer);
        timer = setTimeout(searchNow, 250);
    }

    // Add click handler (event delegation)
    tbody.addEventListener('click', async (e) => {
        const btn = e.target.closest('[data-add]');
        if (!btn) return;

        const productId = btn.getAttribute('data-add');
        const sku = btn.getAttribute('data-sku') || '';
        const qty = '1';

        // reason default (required di controller)
        const reason = `Tambah sparepart ${sku}`;

        const postUrl = `/cashier/transactions/${txId}/part-lines`;
        const body = new URLSearchParams();
        body.set('product_id', productId);
        body.set('qty', qty);
        body.set('reason', reason);

        const res = await fetch(postUrl, {
            method: 'POST',
            headers: {
                'Accept': 'text/html',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': {{ \Illuminate\Support\Js::from(csrf_token()) }},
            },
            body: body.toString(),
        });

        // Setelah add, reload halaman agar part lines/service lines ter-update.
        // pq dipertahankan via URL param yang kita set lewat history.replaceState.
        if (res.ok) {
            window.location.reload();
            return;
        }

        alert('Gagal menambah sparepart. Cek validasi/server.');
    });

    input.addEventListener('input', debounceSearch);

    btnClear.addEventListener('click', () => {
        input.value = '';
        setUrlPQ('');
        hint.textContent = 'Minimal 2 karakter untuk mencari.';
        renderEmpty('Belum ada hasil.');
        input.focus();
    });

    // auto-search bila ada pq di URL
    if ((input.value || '').trim().length >= 2) {
        searchNow();
    }
})();
</script>