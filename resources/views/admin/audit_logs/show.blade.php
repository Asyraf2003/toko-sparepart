@extends('shared.layouts.app')

@section('title', 'Audit Log #'.$a->id)

@section('page_heading')
    <div class="page-heading d-flex flex-wrap justify-content-between align-items-start gap-2">
        <div>
            <h3>Audit Log #{{ $a->id }}</h3>
            <p class="text-muted mb-0">Detail perubahan dan payload audit.</p>
        </div>

        <div class="d-flex gap-2">
            <a class="btn btn-outline-secondary" href="{{ url('/admin/audit-logs') }}">← Back to list</a>
        </div>
    </div>
@endsection

@section('content')
    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Summary</div>

                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0">
                            <tbody>
                            <tr><th style="width: 160px;">Time</th><td>{{ $a->createdAt }}</td></tr>
                            <tr>
                                <th>Actor</th>
                                <td>
                                    <div class="fw-semibold">{{ $a->actorName ?? '-' }}</div>
                                    <div class="text-muted small">{{ $a->actorEmail ?? '' }}</div>
                                    <div class="text-muted small">ID: {{ $a->actorId ?? '-' }}, Role: {{ $a->actorRole ?? '-' }}</div>
                                </td>
                            </tr>
                            <tr><th>Action</th><td><span class="badge bg-secondary">{{ $a->action }}</span></td></tr>
                            <tr><th>Entity</th><td>{{ $a->entityType }} (ID: {{ $a->entityId ?? '-' }})</td></tr>
                            <tr><th>Reason</th><td>{{ $a->reason }}</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12 col-lg-7">
            <div class="card">
                <div class="card-body">
                    <div class="fw-bold mb-2">Meta</div>
                    <pre class="p-3 bg-light rounded mb-0" style="max-height: 360px; overflow:auto;">{{ json_encode($a->meta ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">Before</div>
                    <pre class="p-3 bg-light rounded mb-0" style="max-height: 360px; overflow:auto;">{{ json_encode($a->before ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="fw-bold mb-2">After</div>
                    <pre class="p-3 bg-light rounded mb-0" style="max-height: 360px; overflow:auto;">{{ json_encode($a->after ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </div>
    </div>
@endsection