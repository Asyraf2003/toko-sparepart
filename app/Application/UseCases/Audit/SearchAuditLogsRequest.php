<?php

declare(strict_types=1);

namespace App\Application\UseCases\Audit;

final readonly class SearchAuditLogsRequest
{
    public function __construct(
        public ?string $actor = null,       // search by user name/email (like)
        public ?int $actorId = null,        // exact actor_id
        public ?string $entityType = null,
        public ?int $entityId = null,
        public ?string $action = null,
        public ?string $dateFrom = null,    // YYYY-MM-DD
        public ?string $dateTo = null,      // YYYY-MM-DD
        public int $limit = 200,
    ) {}
}
