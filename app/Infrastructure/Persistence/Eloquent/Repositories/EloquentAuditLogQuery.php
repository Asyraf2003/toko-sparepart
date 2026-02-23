<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\Ports\Repositories\AuditLogQueryPort;
use App\Application\UseCases\Audit\SearchAuditLogsRequest;
use App\Application\UseCases\Audit\SearchAuditLogsResult;
use App\Application\UseCases\Audit\ShowAuditLogResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class EloquentAuditLogQuery implements AuditLogQueryPort
{
    public function search(SearchAuditLogsRequest $req): SearchAuditLogsResult
    {
        $actorCol = $this->resolveActorColumn();

        $q = DB::table('audit_logs')
            ->leftJoin('users', "audit_logs.$actorCol", '=', 'users.id')
            ->select([
                'audit_logs.id',
                'audit_logs.created_at',
                DB::raw("audit_logs.$actorCol as actor_id"),
                'audit_logs.actor_role',
                'users.name as actor_name',
                'users.email as actor_email',
                'audit_logs.action',
                'audit_logs.entity_type',
                'audit_logs.entity_id',
                'audit_logs.reason',
            ])
            ->orderByDesc('audit_logs.id');

        if ($req->actorId !== null) {
            $q->where("audit_logs.$actorCol", '=', $req->actorId);
        }

        $actor = trim((string) ($req->actor ?? ''));
        if ($actor !== '') {
            $q->where(function ($qq) use ($actor) {
                $qq->where('users.name', 'like', '%'.$actor.'%')
                    ->orWhere('users.email', 'like', '%'.$actor.'%');
            });
        }

        $entityType = trim((string) ($req->entityType ?? ''));
        if ($entityType !== '') {
            $q->where('audit_logs.entity_type', '=', $entityType);
        }

        if ($req->entityId !== null) {
            $q->where('audit_logs.entity_id', '=', $req->entityId);
        }

        $action = trim((string) ($req->action ?? ''));
        if ($action !== '') {
            $q->where('audit_logs.action', '=', $action);
        }

        $dateFrom = trim((string) ($req->dateFrom ?? ''));
        if ($dateFrom !== '') {
            $q->where('audit_logs.created_at', '>=', $dateFrom.' 00:00:00');
        }

        $dateTo = trim((string) ($req->dateTo ?? ''));
        if ($dateTo !== '') {
            $q->where('audit_logs.created_at', '<=', $dateTo.' 23:59:59');
        }

        $limit = $req->limit > 0 ? min($req->limit, 500) : 200;

        /** @var list<object> $rows */
        $rows = $q->limit($limit)->get()->all();

        $out = array_map(static function (object $r): array {
            return [
                'id' => (int) $r->id,
                'created_at' => (string) $r->created_at,
                'actor_id' => $r->actor_id !== null ? (int) $r->actor_id : null,
                'actor_role' => $r->actor_role !== null ? (string) $r->actor_role : null,
                'actor_name' => $r->actor_name !== null ? (string) $r->actor_name : null,
                'actor_email' => $r->actor_email !== null ? (string) $r->actor_email : null,
                'action' => (string) $r->action,
                'entity_type' => (string) $r->entity_type,
                'entity_id' => $r->entity_id !== null ? (int) $r->entity_id : null,
                'reason' => (string) $r->reason,
            ];
        }, $rows);

        return new SearchAuditLogsResult($out);
    }

    public function findById(int $auditLogId): ?ShowAuditLogResult
    {
        $actorCol = $this->resolveActorColumn();

        $r = DB::table('audit_logs')
            ->leftJoin('users', "audit_logs.$actorCol", '=', 'users.id')
            ->where('audit_logs.id', '=', $auditLogId)
            ->first([
                'audit_logs.*',
                DB::raw("audit_logs.$actorCol as actor_id"),
                'users.name as actor_name',
                'users.email as actor_email',
            ]);

        if ($r === null) {
            return null;
        }

        $before = null;
        $after = null;
        $meta = null;

        try {
            $before = $r->before !== null ? json_decode((string) $r->before, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable) {
            $before = null;
        }

        try {
            $after = $r->after !== null ? json_decode((string) $r->after, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable) {
            $after = null;
        }

        try {
            $meta = $r->meta !== null ? json_decode((string) $r->meta, true, 512, JSON_THROW_ON_ERROR) : null;
        } catch (\Throwable) {
            $meta = null;
        }

        return new ShowAuditLogResult(
            id: (int) $r->id,
            actorId: $r->actor_id !== null ? (int) $r->actor_id : null,
            actorRole: $r->actor_role !== null ? (string) $r->actor_role : null,
            actorName: $r->actor_name !== null ? (string) $r->actor_name : null,
            actorEmail: $r->actor_email !== null ? (string) $r->actor_email : null,
            action: (string) $r->action,
            entityType: (string) $r->entity_type,
            entityId: $r->entity_id !== null ? (int) $r->entity_id : null,
            reason: (string) $r->reason,
            before: is_array($before) ? $before : null,
            after: is_array($after) ? $after : null,
            meta: is_array($meta) ? $meta : null,
            createdAt: (string) $r->created_at,
        );
    }

    private function resolveActorColumn(): string
    {
        if (Schema::hasColumn('audit_logs', 'actor_id')) {
            return 'actor_id';
        }
        if (Schema::hasColumn('audit_logs', 'actor_user_id')) {
            return 'actor_user_id';
        }

        throw new \RuntimeException('audit_logs actor column not found (expected actor_id or actor_user_id)');
    }
}
