@extends('shared.layouts.app')

@section('title', 'Bukti Bayar Telegram')

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Bukti Bayar (PENDING)</h3>
            <p class="text-muted mb-0">Approval bukti bayar yang dikirim lewat Telegram.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-light" href="{{ url('/admin/telegram') }}">Kembali</a>
        </div>
    </div>
@endsection

@section('content')
    @php
        $fmt = function (int $v): string {
            return number_format((float) $v, 0, ',', '.');
        };
    @endphp

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 90px;">ID</th>
                        <th style="width: 180px;">Created</th>
                        <th>No Faktur</th>
                        <th>Supplier</th>
                        <th class="text-end" style="width: 160px;">Total</th>
                        <th style="width: 220px;">File</th>
                        <th style="width: 120px;">Aksi</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($rows as $r)
                        <tr>
                            <td class="fw-semibold">{{ $r->id }}</td>
                            <td class="text-muted">{{ $r->created_at }}</td>
                            <td class="fw-semibold">{{ $r->no_faktur }}</td>
                            <td>{{ $r->supplier_name }}</td>
                            <td class="text-end">{{ $fmt((int) $r->grand_total) }}</td>
                            <td class="text-muted">{{ $r->original_filename ?? '-' }}</td>
                            <td>
                                <a class="btn btn-sm btn-outline-primary"
                                   href="{{ url('/admin/telegram/payment-proofs/'.$r->id) }}">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted">Tidak ada data</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection