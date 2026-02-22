<div class="card mt-3">
    <div class="card-header">
        <h4>Pembayaran CASH</h4>
    </div>
    <div class="card-body">
        <div>
            <div>Grand Total: <b>{{ $grossTotal }}</b></div>
            <div>Cash Rounded Total: <b>{{ $roundedCashTotal }}</b> (rounding: {{ $cashRoundingAmount }})</div>
        </div>

        <div class="mt-3" style="max-width: 220px;">
            <label class="form-label">Cash diterima</label>
            <input id="cash_received" type="number" min="0" value="0" class="form-control">
        </div>

        <div class="mt-3">
            <div>Kembalian (pakai rounded total): <b id="cash_change">0</b></div>
        </div>
    </div>
</div>

<script>
(function () {
    var total = {{ (int) ($roundedCashTotal ?? 0) }};
    var input = document.getElementById('cash_received');
    var out = document.getElementById('cash_change');

    function calc() {
        var received = parseInt(input.value || '0', 10);
        if (isNaN(received)) received = 0;
        var change = received - total;
        out.textContent = String(change);
    }

    input.addEventListener('input', calc);
    calc();
})();
</script>