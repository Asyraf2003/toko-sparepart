<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

final readonly class UpdatePayrollPeriodRequest
{
    /**
     * @param  list<UpdatePayrollPeriodLine>  $lines
     */
    public function __construct(
        public int $actorUserId,
        public int $payrollPeriodId,
        public string $weekStart, // Y-m-d
        public string $weekEnd,   // Y-m-d
        public ?string $note,
        public string $reason,
        public array $lines,
    ) {}
}
