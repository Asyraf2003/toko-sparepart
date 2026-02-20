<h3>Aksi Nota</h3>

<div>
    <div>Total Sparepart: {{ $partsTotal }}</div>
    <div>Total Service: {{ $serviceTotal }}</div>
    <div>Grand Total: {{ $grossTotal }}</div>
    <div>Cash Rounded Total: {{ $roundedCashTotal }} (rounding: {{ $cashRoundingAmount }})</div>
</div>

@if ($tx->status !== 'VOID')
    <form method="post" action="/cashier/transactions/{{ $tx->id }}/open" style="margin-top:8px;">
        @csrf
        <button type="submit">SIMPAN OPEN (UNPAID)</button>
    </form>

    <form method="post" action="/cashier/transactions/{{ $tx->id }}/complete-cash" style="margin-top:8px;">
        @csrf
        <button type="submit">COMPLETE CASH</button>
    </form>

    <form method="post" action="/cashier/transactions/{{ $tx->id }}/complete-transfer" style="margin-top:8px;">
        @csrf
        <button type="submit">COMPLETE TRANSFER</button>
    </form>

    <form method="post" action="/cashier/transactions/{{ $tx->id }}/void" style="margin-top:8px;">
        @csrf
        <label>Reason VOID:</label>
        <input type="text" name="reason" required>
        <button type="submit">VOID</button>
    </form>
@endif

@if (($tx->status ?? '') === 'OPEN')
    <div style="margin-top:8px;">
        <a href="/cashier/transactions/{{ $tx->id }}/work-order" target="_blank">Print Work Order</a>
    </div>
@endif