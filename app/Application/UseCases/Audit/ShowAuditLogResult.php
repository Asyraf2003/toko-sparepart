<?php

declare(strict_types=1);

namespace App\Application\UseCases\Audit;

final readonly class ShowAuditLogResult
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $meta
     */
    public function __construct(
        public int $id,
        public ?int $actorId,
        public ?string $actorRole,
        public ?string $actorName,
        public ?string $actorEmail,
        public string $action,
        public string $entityType,
        public ?int $entityId,
        public string $reason,
        public ?array $before,
        public ?array $after,
        public ?array $meta,
        public string $createdAt,
    ) {}
}
