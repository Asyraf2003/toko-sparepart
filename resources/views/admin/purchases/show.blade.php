@extends('shared.layouts.app')

@section('title', 'Detail Pembelian')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Detail Pembelian</h3>
            <p class="text-muted mb-0">
                Faktur: <span class="fw-semibold">{{ $invoice->no_faktur }}</span> · Supplier: {{ $invoice->supplier_name }}
            </p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-primary" href="{{ url('/admin/purchases/'.$invoice->id.'/edit') }}">Edit Header</a>
            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">← Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function ($v): string {
            if ($v === null) return '-';
            if (is_numeric($v)) return number_format((float) $v, 0, ',', '.');
            return (string) $v;
        };
        $createdBy = $invoice->created_by_name ?: ('User#'.$invoice->created_by_user_id);

        $ps = $invoice->payment_status ?? 'UNPAID';
        $isPaid = strtoupper((string) $ps) === 'PAID';
        $due = $invoice->due_date;
        $badge = null;

        if (!$isPaid && $due !== null && (string)$due !== '') {
            if ((string)$due < (string)$today) {
                $badge = ['bg' => 'bg-danger', 'text' => 'OVERDUE'];
            } elseif ((string)$due === (string)$targetDue) {
                $badge = ['bg' => 'bg-warning text-dark', 'text' => 'H-5'];
            }
        }

        $paidBy = $invoice->paid_by_name ?: ($invoice->paid_by_user_id ? ('User#'.$invoice->paid_by_user_id) : '-');
    @endphp

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Header</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width:180px;">Tgl Kirim</th><td>{{ $invoice->tgl_kirim }}</td></tr>
                            <tr><th>Due Date</th>
                                <td>
                                    {{ $invoice->due_date ?? '-' }}
                                    @if ($badge !== null)
                                        <span class="badge {{ $badge['bg'] }} ms-1">{{ $badge['text'] }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><th>No Faktur</th><td>{{ $invoice->no_faktur }}</td></tr>
                            <tr><th>Supplier</th><td>{{ $invoice->supplier_name }}</td></tr>
                            <tr><th>Kepada</th><td>{{ $invoice->kepada ?? '-' }}</td></tr>
                            <tr><th>No Pesanan</th><td>{{ $invoice->no_pesanan ?? '-' }}</td></tr>
                            <tr><th>Nama Sales</th><td>{{ $invoice->nama_sales ?? '-' }}</td></tr>
                            <tr><th>Catatan</th><td>{{ $invoice->note ?? '-' }}</td></tr>
                            <tr><th>Dibuat</th><td>{{ $invoice->created_at }} · {{ $createdBy }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Pembayaran</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr>
                                <th style="width:180px;">Status</th>
                                <td>
                                    @if ($isPaid)
                                        <span class="badge bg-success">PAID</span>
                                    @else
                                        <span class="badge bg-danger">UNPAID</span>
                                    @endif
                                </td>
                            </tr>
                            <tr><th>Paid At</th><td>{{ $invoice->paid_at ?? '-' }}</td></tr>
                            <tr><th>Paid By</th><td>{{ $paidBy }}</td></tr>
                            <tr><th>Paid Note</th><td>{{ $invoice->paid_note ?? '-' }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        @if ($isPaid)
                            <form method="post" action="{{ url('/admin/purchases/'.$invoice->id.'/mark-unpaid') }}" class="d-flex flex-column gap-2">
                                @csrf
                                <div>
                                    <label class="form-label">Alasan (wajib)</label>
                                    <input class="form-control" type="text" name="reason" maxlength="255" required>
                                </div>
                                <button class="btn btn-outline-danger" type="submit">Set UNPAID</button>
                            </form>
                        @else
                            <form method="post" action="{{ url('/admin/purchases/'.$invoice->id.'/mark-paid') }}" class="d-flex flex-column gap-2">
                                @csrf
                                <div>
                                    <label class="form-label">Paid Note (opsional)</label>
                                    <input class="form-control" type="text" name="paid_note" maxlength="255">
                                </div>
                                <div>
                                    <label class="form-label">Alasan (wajib)</label>
                                    <input class="form-control" type="text" name="reason" maxlength="255" required>
                                    <div class="form-text">Dicatat ke audit log.</div>
                                </div>
                                <button class="btn btn-success" type="submit">Mark PAID</button>
                            </form>
                        @endif
                    </div>

                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Bukti Bayar (Telegram)</div>

                    @if (!isset($proofs) || (is_countable($proofs) && count($proofs) === 0))
                        <div class="text-muted">Belum ada submission bukti bayar dari Telegram.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-striped align-middle mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 90px;">ID</th>
                                    <th style="width: 120px;">Status</th>
                                    <th>File</th>
                                    <th style="width: 170px;">Waktu</th>
                                    <th style="width: 160px;">Aksi</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach ($proofs as $p)
                                    @php
                                        $status = strtoupper((string) data_get($p, 'status', 'PENDING'));
                                        $badge = 'bg-secondary';
                                        if ($status === 'PENDING') $badge = 'bg-warning text-dark';
                                        if ($status === 'APPROVED') $badge = 'bg-success';
                                        if ($status === 'REJECTED') $badge = 'bg-danger';

                                        $fname = (string) data_get($p, 'original_filename', '-');
                                        $created = (string) data_get($p, 'created_at', '');
                                    @endphp
                                    <tr>
                                        <td>#{{ (int) data_get($p, 'id', 0) }}</td>
                                        <td><span class="badge {{ $badge }}">{{ $status }}</span></td>
                                        <td>{{ $fname !== '' ? $fname : '-' }}</td>
                                        <td class="text-muted">{{ $created }}</td>
                                        <td class="d-flex gap-2">
                                            <a class="btn btn-sm btn-outline-primary"
                                            href="{{ url('/admin/telegram/payment-proofs/'.(int) data_get($p, 'id', 0)) }}">
                                                Review
                                            </a>
                                            <a class="btn btn-sm btn-outline-secondary"
                                            href="{{ url('/admin/telegram/payment-proofs/'.(int) data_get($p, 'id', 0).'/download') }}">
                                                Download
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-muted small mt-2">
                            Catatan: approval/reject dilakukan dari halaman Review agar audit trail tetap konsisten.
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Baris</div>

                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle mb-0">
                            <thead>
                            <tr>
                                <th style="width:90px;">SKU</th>
                                <th>Nama</th>
                                <th class="text-end" style="width:90px;">Qty</th>
                                <th class="text-end" style="width:150px;">Harga Satuan</th>
                                <th class="text-end" style="width:120px;">Diskon (%)</th>
                                <th class="text-end" style="width:160px;">Total Baris</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($lines as $l)
                                @php
                                    $discPct = number_format(((int) $l->disc_bps) / 100, 2, ',', '.');
                                @endphp
                                <tr>
                                    <td>{{ $l->sku }}</td>
                                    <td>{{ $l->name }}</td>
                                    <td class="text-end">{{ $l->qty }}</td>
                                    <td class="text-end">{{ $fmt($l->unit_cost) }}</td>
                                    <td class="text-end">{{ $discPct }}</td>
                                    <td class="text-end">{{ $fmt($l->line_total) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-muted">Tidak ada baris</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="text-muted mt-2">
                        Catatan: pajak disimpan di header (total_pajak). Alokasi pajak per-baris tidak disimpan.
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection