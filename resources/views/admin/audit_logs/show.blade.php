@extends('shared.layouts.app')

@section('title', 'Catatan Audit #'.$a->id)

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Catatan Audit #{{ $a->id }}</h3>
            <p class="text-muted mb-0">Detail perubahan dan payload audit.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/audit-logs') }}">← Kembali ke daftar</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Ringkasan</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width: 160px;">Waktu</th><td>{{ $a->createdAt }}</td></tr>
                            <tr>
                                <th>Pelaku</th>
                                <td>
                                    <div class="fw-semibold">{{ $a->actorName ?? '-' }}</div>
                                    <div class="text-muted small">{{ $a->actorEmail ?? '' }}</div>
                                    <div class="text-muted small">ID: {{ $a->actorId ?? '-' }}, Peran: {{ $a->actorRole ?? '-' }}</div>
                                </td>
                            </tr>
                            <tr><th>Aksi</th><td><span class="badge bg-secondary">{{ $a->action }}</span></td></tr>
                            <tr><th>Entitas</th><td>{{ $a->entityType }} (ID: {{ $a->entityId ?? '-' }})</td></tr>
                            <tr><th>Alasan</th><td>{{ $a->reason }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
@endsection