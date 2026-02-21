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
        $meta = $entry->meta ?? [];

        // Optional metadata (selaras dengan kontrak docs: ip/user_agent opsional)
        try {
            $req = request();
            if ($req !== null) {
                $ip = $req->ip();
                $ua = $req->userAgent();

                if (is_string($ip) && $ip !== '') {
                    $meta['ip'] = $ip;
                }
                if (is_string($ua) && $ua !== '') {
                    $meta['user_agent'] = $ua;
                }
            }
        } catch (\Throwable) {
            // ignore if request() is not available (CLI edge)
        }

        $meta = (is_array($meta) && count($meta) > 0) ? $meta : null;

        DB::table('audit_logs')->insert([
            'actor_user_id' => $entry->actorId,
            'actor_role' => $entry->actorRole,
            'action' => $entry->action,
            'entity_type' => $entry->entityType,
            'entity_id' => $entry->entityId,
            'reason' => $entry->reason,
            'before_json' => $entry->before ? json_encode($entry->before) : null,
            'after_json' => $entry->after ? json_encode($entry->after) : null,
            'ip' => $meta['ip'] ?? null,
            'user_agent' => $meta['user_agent'] ?? null,
        ]);
    }
}
