<?php

declare(strict_types=1);

namespace App\Application\UseCases\Audit;

final readonly class SearchAuditLogsResult
{
    /**
     * @param  list<array<string, mixed>>  $rows
     */
    public function __construct(
        public array $rows,
    ) {}
}
