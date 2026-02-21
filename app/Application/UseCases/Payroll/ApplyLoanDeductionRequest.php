<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

final readonly class ApplyLoanDeductionRequest
{
    public function __construct(
        public int $actorUserId,
        public int $payrollPeriodId,
    ) {}
}
