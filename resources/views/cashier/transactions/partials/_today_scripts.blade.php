<script>
(function () {
    const form = document.getElementById('today_filter_form');
    const qInput = document.getElementById('today_q');
    const statusSelect = document.getElementById('today_status');
    const listWrap = document.getElementById('today_list');
    const totalEl = document.getElementById('today_total');
    const hint = document.getElementById('today_hint');
    const applyBtn = document.getElementById('today_apply_btn');

    if (!form || !qInput || !statusSelect || !listWrap) return;

    let timer = null;
    let loading = false;

    function setHint(text) {
        if (hint) hint.textContent = text || '';
    }

    function setLoading(isLoading) {
        loading = isLoading;
        if (applyBtn) applyBtn.disabled = isLoading;
        qInput.disabled = isLoading;
        statusSelect.disabled = isLoading;
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

            const total = root.getAttribute('data-total');
            if (totalEl && total !== null) totalEl.textContent = total;

            if (pushState) {
                window.history.pushState({}, '', pageUrl);
            }

            setLoading(false);
        } catch (e) {
            setLoading(false);
            window.location.href = pageUrl;
        }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const u = new URL(form.action, window.location.origin);
        const fd = new FormData(form);

        for (const [k, v] of fd.entries()) {
            const val = String(v || '').trim();
            if (val !== '') u.searchParams.set(k, val);
        }

        u.searchParams.delete('page');

        load(u.toString(), true);
    });

    qInput.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(function () {
            const ev = new Event('submit', { cancelable: true });
            form.dispatchEvent(ev);
        }, 300);
    });

    statusSelect.addEventListener('change', function () {
        const ev = new Event('submit', { cancelable: true });
        form.dispatchEvent(ev);
    });

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

    window.addEventListener('popstate', function () {
        if (loading) return;
        load(window.location.href, false);
    });
})();
</script>