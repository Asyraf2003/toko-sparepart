<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final class EloquentAuditLogger implements AuditLoggerPort
{
    public function append(AuditEntry $entry): void
    {
        DB::table('audit_logs')->insert([
            'actor_user_id' => $entry->actorUserId,
            'actor_role' => $entry->actorRole,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId,
            'action' => $entry->action,
            'reason' => $entry->reason,
            'before_json' => $entry->beforeJson,
            'after_json' => $entry->afterJson,
            'ip' => $entry->ip,
            'user_agent' => $entry->userAgent,
            // created_at pakai default DB (useCurrent)
        ]);
    }
}