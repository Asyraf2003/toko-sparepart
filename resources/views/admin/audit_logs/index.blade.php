<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit Logs</title>
    <style>
        body { font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif; padding: 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; vertical-align: top; }
        th { background: #f6f6f6; text-align: left; }
        .filters { display: grid; grid-template-columns: repeat(6, 1fr); gap: 8px; margin-bottom: 12px; }
        .filters label { display: block; font-size: 12px; color: #333; margin-bottom: 4px; }
        .filters input { width: 100%; padding: 6px; }
        .actions { margin-top: 8px; }
        .muted { color: #666; font-size: 12px; }
        a { color: #0a58ca; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <h1>Audit Logs</h1>
    <p class="muted">Filter: actor/entity/action/date. Default limit 200.</p>

    <form method="GET" action="/admin/audit-logs">
        <div class="filters">
            <div>
                <label>Actor (name/email contains)</label>
                <input type="text" name="actor" value="{{ $filters['actor'] }}">
            </div>
            <div>
                <label>Actor ID</label>
                <input type="text" name="actor_id" value="{{ $filters['actor_id'] }}">
            </div>
            <div>
                <label>Entity Type</label>
                <input type="text" name="entity_type" value="{{ $filters['entity_type'] }}" placeholder="Transaction/Product/...">
            </div>
            <div>
                <label>Entity ID</label>
                <input type="text" name="entity_id" value="{{ $filters['entity_id'] }}">
            </div>
            <div>
                <label>Action</label>
                <input type="text" name="action" value="{{ $filters['action'] }}" placeholder="VOID/PRICE_CHANGE/...">
            </div>
            <div>
                <label>Date From</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}">
            </div>
            <div>
                <label>Date To</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}">
            </div>
        </div>

        <div class="actions">
            <button type="submit">Apply Filter</button>
            <a href="/admin/audit-logs" style="margin-left: 8px;">Reset</a>
        </div>
    </form>

    <hr>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Time</th>
                <th>Actor</th>
                <th>Action</th>
                <th>Entity</th>
                <th>Reason</th>
                <th>Detail</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($rows as $r)
            <tr>
                <td>{{ $r['id'] }}</td>
                <td>{{ $r['created_at'] }}</td>
                <td>
                    <div>{{ $r['actor_name'] ?? '-' }}</div>
                    <div class="muted">{{ $r['actor_email'] ?? '' }}</div>
                    <div class="muted">ID: {{ $r['actor_id'] ?? '-' }}</div>
                </td>
                <td>{{ $r['action'] }}</td>
                <td>
                    <div>{{ $r['entity_type'] }}</div>
                    <div class="muted">ID: {{ $r['entity_id'] ?? '-' }}</div>
                </td>
                <td>{{ $r['reason'] }}</td>
                <td><a href="/admin/audit-logs/{{ $r['id'] }}">Open</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</body>
</html>