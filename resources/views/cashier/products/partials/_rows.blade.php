@php
    $rows = $rows ?? collect();
    $canSearch = (bool) ($canSearch ?? false);
    $txId = $txId ?? null;

    $fmt = function ($n) {
        return number_format((int) $n, 0, ',', '.');
    };
@endphp

@if (!$canSearch)
    <tr>
        <td colspan="7" class="text-muted">Belum ada hasil.</td>
    </tr>
@else
    @if ($rows->count() === 0)
        <tr>
            <td colspan="7" class="text-muted">Tidak ada hasil.</td>
        </tr>
    @else
        @foreach ($rows as $p)
            <tr>
                <td>{{ $p->sku }}</td>
                <td>{{ $p->name }}</td>
                <td>{{ $fmt($p->sell_price_current ?? 0) }}</td>
                <td>{{ (int) ($p->on_hand_qty ?? 0) }}</td>
                <td>{{ (int) ($p->reserved_qty ?? 0) }}</td>
                <td>{{ (int) ($p->available_qty ?? 0) }}</td>
                <td style="min-width: 200px;">
                    @if ($txId !== null)
                        <form method="post"
                              action="{{ url('/cashier/transactions/'.$txId.'/part-lines') }}"
                              class="m-0 d-flex gap-2 align-items-center"
                              data-add-part-form="1">
                            @csrf
                            <input type="hidden" name="product_id" value="{{ (int) $p->id }}">
                            <input type="hidden" name="qty" value="1">
                            <input type="hidden" name="reason" value="Tambah sparepart {{ $p->sku }}">

                            <button type="submit" class="btn btn-sm btn-success w-100">
                                Tambah
                            </button>
                        </form>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
            </tr>
        @endforeach
    @endif
@endif