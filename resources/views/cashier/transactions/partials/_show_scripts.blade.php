<script>
(function () {
    const root = document.getElementById('tx_show_root');
    if (!root) return;

    const txId = root.getAttribute('data-tx-id');
    const csrf = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';

    const FRAG_IDS = [
        'tx_alerts',
        'tx_part_lines',
        'tx_service_lines',
        'tx_customer_form',
        'tx_cash_calculator',
        'tx_summary_actions',
    ];

    function formatRupiahInt(n) {
        n = parseInt(String(n || '0'), 10);
        if (isNaN(n)) n = 0;
        const sign = n < 0 ? '-' : '';
        const s = String(Math.abs(n));
        return sign + s.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function initCashCalculator() {
        const calcRoot = document.getElementById('cash_calc_root');
        if (!calcRoot) return;

        const input = document.getElementById('cash_received');
        const out = document.getElementById('cash_change');
        const shortWrap = document.getElementById('cash_short_wrap');
        const shortOut = document.getElementById('cash_short');

        // kalau input tidak ada berarti mode PAID/DRAFT -> no-op
        if (!input || !out) return;

        const total = parseInt(calcRoot.getAttribute('data-rounded-total') || '0', 10) || 0;

        function calc() {
            let received = parseInt(input.value || '0', 10);
            if (isNaN(received)) received = 0;

            const change = received - total;

            if (change < 0) {
                out.textContent = '0';
                if (shortWrap) shortWrap.classList.remove('d-none');
                if (shortOut) shortOut.textContent = formatRupiahInt(Math.abs(change));
            } else {
                out.textContent = formatRupiahInt(change);
                if (shortWrap) shortWrap.classList.add('d-none');
                if (shortOut) shortOut.textContent = '0';
            }

            const hidden = document.getElementById('cash_received_hidden');
            if (hidden) hidden.value = String(received);

            const btn = document.getElementById('btn_complete_cash_calc');
            if (btn) btn.disabled = received < total;
        }

        input.addEventListener('input', calc);
        calc();
    }

    async function refreshFragments() {
        const u = new URL(window.location.href);
        u.searchParams.set('fragment', '1');

        const res = await fetch(u.toString(), {
            headers: { 'Accept': 'text/html' }
        });

        if (!res.ok) {
            throw new Error('fragment fetch failed');
        }

        const html = await res.text();
        const doc = new DOMParser().parseFromString(html, 'text/html');

        for (const id of FRAG_IDS) {
            const nextEl = doc.getElementById(id);
            const curEl = document.getElementById(id);
            if (nextEl && curEl) {
                curEl.replaceWith(nextEl);
            }
        }

        initCashCalculator();
    }

    function mergeCustomerFields(fd) {
        const wrap = document.getElementById('tx_customer_form');
        if (!wrap) return;

        const fields = ['customer_name', 'customer_phone', 'vehicle_plate', 'note'];
        for (const name of fields) {
            const el = wrap.querySelector('[name="' + name + '"]');
            if (!el) continue;
            fd.set(name, String(el.value || ''));
        }
    }

    async function submitFormAjax(form) {
        const action = form.getAttribute('action') || '';
        const method = (form.getAttribute('method') || 'GET').toUpperCase();

        // intercept hanya POST
        if (method !== 'POST') return false;

        const fd = new FormData(form);

        // Barengan: kalau action /open, ikut kirim field customer
        if (action.endsWith('/open')) {
            mergeCustomerFields(fd);
        }

        const body = new URLSearchParams();
        for (const [k, v] of fd.entries()) {
            body.set(k, String(v));
        }

        const res = await fetch(action, {
            method: 'POST',
            headers: {
                'Accept': 'text/html',
                'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: body.toString(),
            redirect: 'follow',
        });

        // Redirect ke today (complete cash/transfer atau void yang sudah Anda ubah)
        if (res.redirected && res.url) {
            if (res.url.includes('/cashier/transactions/today')) {
                window.location.href = res.url;
                return true;
            }
        }

        // selain itu => refresh fragments
        await refreshFragments();
        return true;
    }

    // Expose untuk product search
    window.APPKASIR_TX = {
        refresh: refreshFragments,
    };

    // Delegation: intercept semua submit di area nota
    root.addEventListener('submit', function (e) {
        const form = e.target.closest('form');
        if (!form) return;

        const action = form.getAttribute('action') || '';
        const isTxAction =
            action.includes('/cashier/transactions/' + txId + '/')
            || action.endsWith('/cashier/transactions/' + txId + '/open');

        if (!isTxAction) return;

        e.preventDefault();

        submitFormAjax(form).catch(function () {
            // fallback: submit normal
            form.submit();
        });
    });

    // init awal
    initCashCalculator();
})();
</script>