<?php

declare(strict_types=1);

namespace App\Application\UseCases\Audit;

use App\Application\Ports\Repositories\AuditLogQueryPort;

final readonly class ShowAuditLogUseCase
{
    public function __construct(
        private AuditLogQueryPort $q,
    ) {}

    public function handle(int $auditLogId): ?ShowAuditLogResult
    {
        return $this->q->findById($auditLogId);
    }
}
