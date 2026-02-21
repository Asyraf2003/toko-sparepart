<?php

declare(strict_types=1);

namespace App\Application\Ports\Repositories;

use App\Application\UseCases\Audit\SearchAuditLogsRequest;
use App\Application\UseCases\Audit\SearchAuditLogsResult;
use App\Application\UseCases\Audit\ShowAuditLogResult;

interface AuditLogQueryPort
{
    public function search(SearchAuditLogsRequest $req): SearchAuditLogsResult;

    public function findById(int $auditLogId): ?ShowAuditLogResult;
}
