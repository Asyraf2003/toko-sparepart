<hr>
<h3>Pembayaran CASH</h3>

<div>
    <div>Grand Total: <b>{{ $grossTotal }}</b></div>
    <div>Cash Rounded Total: <b>{{ $roundedCashTotal }}</b> (rounding: {{ $cashRoundingAmount }})</div>
</div>

<div style="margin-top:8px;">
    <label>Cash diterima:</label>
    <input id="cash_received" type="number" min="0" value="0" style="width:140px;">
</div>

<div style="margin-top:8px;">
    <div>Kembalian (pakai rounded total): <b id="cash_change">0</b></div>
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