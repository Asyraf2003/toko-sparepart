<?php

declare(strict_types=1);

namespace App\Application\UseCases\Audit;

use App\Application\Ports\Repositories\AuditLogQueryPort;

final readonly class SearchAuditLogsUseCase
{
    public function __construct(
        private AuditLogQueryPort $q,
    ) {}

    public function handle(SearchAuditLogsRequest $req): SearchAuditLogsResult
    {
        return $this->q->search($req);
    }
}
