<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Audit\SearchAuditLogsRequest;
use App\Application\UseCases\Audit\SearchAuditLogsUseCase;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuditLogIndexController
{
    public function __invoke(Request $request, SearchAuditLogsUseCase $uc): View
    {
        $actorId = $request->query('actor_id');
        $entityId = $request->query('entity_id');

        $result = $uc->handle(new SearchAuditLogsRequest(
            actor: $request->query('actor'),
            actorId: (is_string($actorId) && ctype_digit($actorId)) ? (int) $actorId : null,
            entityType: $request->query('entity_type'),
            entityId: (is_string($entityId) && ctype_digit($entityId)) ? (int) $entityId : null,
            action: $request->query('action'),
            dateFrom: $request->query('date_from'),
            dateTo: $request->query('date_to'),
            limit: 200,
        ));

        return view('admin.audit_logs.index', [
            'rows' => $result->rows,
            'filters' => [
                'actor' => (string) $request->query('actor', ''),
                'actor_id' => (string) $request->query('actor_id', ''),
                'entity_type' => (string) $request->query('entity_type', ''),
                'entity_id' => (string) $request->query('entity_id', ''),
                'action' => (string) $request->query('action', ''),
                'date_from' => (string) $request->query('date_from', ''),
                'date_to' => (string) $request->query('date_to', ''),
            ],
        ]);
    }
}
