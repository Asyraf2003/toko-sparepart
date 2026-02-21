<?php

declare(strict_types=1);

namespace App\Domain\Audit;

final readonly class AuditEntry
{
    /**
     * @param array<string, mixed>|null $beforeJson
     * @param array<string, mixed>|null $afterJson
     */
    public function __construct(
        public ?int $actorUserId,
        public ?string $actorRole,
        public string $entityType,
        public ?int $entityId,
        public string $action,
        public ?string $reason,
        public ?array $beforeJson,
        public ?array $afterJson,
        public ?string $ip,
        public ?string $userAgent,
    ) {
    }
}