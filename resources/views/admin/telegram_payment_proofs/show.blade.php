@extends('shared.layouts.app')

@section('title', 'Detail Bukti Bayar')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Detail Bukti Bayar</h3>
            <p class="text-muted mb-0">Submission #{{ $row->id }}</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-light" href="{{ url('/admin/telegram/payment-proofs') }}">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function (int $v): string {
            return number_format((float) $v, 0, ',', '.');
        };
    @endphp

    <div class="row">
        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-3">Invoice</h5>

                    <div class="mb-2"><span class="text-muted">No Faktur:</span> <span class="fw-semibold">{{ $row->no_faktur }}</span></div>
                    <div class="mb-2"><span class="text-muted">Supplier:</span> {{ $row->supplier_name }}</div>
                    <div class="mb-2"><span class="text-muted">Total:</span> <span class="fw-semibold">{{ $fmt((int)$row->grand_total) }}</span></div>

                    <div class="mb-2">
                        <span class="text-muted">Invoice Status:</span>
                        @if(($row->payment_status ?? '') === 'PAID')
                            <span class="badge bg-success">PAID</span>
                        @else
                            <span class="badge bg-secondary">{{ $row->payment_status ?? '-' }}</span>
                        @endif
                    </div>

                    <div class="mb-2">
                        <span class="text-muted">Submission Status:</span>
                        @if($row->status === 'PENDING')
                            <span class="badge bg-warning">PENDING</span>
                        @elseif($row->status === 'APPROVED')
                            <span class="badge bg-success">APPROVED</span>
                        @elseif($row->status === 'REJECTED')
                            <span class="badge bg-danger">REJECTED</span>
                        @else
                            <span class="badge bg-secondary">{{ $row->status }}</span>
                        @endif
                    </div>

                    <div class="mt-3">
                        <a class="btn btn-outline-primary"
                           href="{{ url('/admin/telegram/payment-proofs/'.$row->id.'/download') }}">
                            Download Bukti
                        </a>
                    </div>
                </div>
            </div>
        </div>

        @if($row->status === 'PENDING')
            <div class="col-12 col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h5 class="mb-3">Approve</h5>

                        <form method="post" action="{{ url('/admin/telegram/payment-proofs/'.$row->id.'/approve') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Catatan / Reason</label>
                                <input class="form-control" type="text" name="note" value="approved via admin ui">
                            </div>

                            <button class="btn btn-success" type="submit">Approve</button>
                        </form>

                        <hr>

                        <h5 class="mb-3">Reject</h5>

                        <form method="post" action="{{ url('/admin/telegram/payment-proofs/'.$row->id.'/reject') }}">
                            @csrf
                            <div class="mb-2">
                                <label class="form-label">Alasan</label>
                                <input class="form-control" type="text" name="note" value="rejected via admin ui">
                            </div>

                            <button class="btn btn-danger" type="submit">Reject</button>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection