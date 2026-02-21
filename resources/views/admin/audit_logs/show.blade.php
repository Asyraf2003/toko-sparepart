<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Log #{{ $a->id }}</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 16px; }
        pre { background: #f6f6f6; padding: 12px; overflow: auto; }
        .row { margin-bottom: 10px; }
        .muted { color: #666; font-size: 12px; }
        a { color: #0a58ca; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <p><a href="/admin/audit-logs">← Back to list</a></p>

    <h1>Audit Log #{{ $a->id }}</h1>

    <div class="row"><strong>Time:</strong> {{ $a->createdAt }}</div>
    <div class="row">
        <strong>Actor:</strong>
        {{ $a->actorName ?? '-' }} <span class="muted">{{ $a->actorEmail ?? '' }}</span>
        <span class="muted">(ID: {{ $a->actorId ?? '-' }}, Role: {{ $a->actorRole ?? '-' }})</span>
    </div>
    <div class="row"><strong>Action:</strong> {{ $a->action }}</div>
    <div class="row"><strong>Entity:</strong> {{ $a->entityType }} (ID: {{ $a->entityId ?? '-' }})</div>
    <div class="row"><strong>Reason:</strong> {{ $a->reason }}</div>

    <h2>Meta</h2>
    <pre>{{ json_encode($a->meta ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

    <h2>Before</h2>
    <pre>{{ json_encode($a->before ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>

    <h2>After</h2>
    <pre>{{ json_encode($a->after ?? null, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
</body>
</html>