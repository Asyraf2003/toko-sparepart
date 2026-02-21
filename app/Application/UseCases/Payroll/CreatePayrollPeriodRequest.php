<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

final readonly class CreatePayrollPeriodRequest
{
    /**
     * @param  list<CreatePayrollPeriodLine>  $lines
     */
    public function __construct(
        public int $actorUserId,
        public string $weekStart, // Y-m-d (must be Monday)
        public string $weekEnd,   // Y-m-d (must be Saturday)
        public ?string $note,
        public array $lines,
    ) {}
}
