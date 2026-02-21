<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

final readonly class CreatePayrollPeriodLine
{
    public function __construct(
        public int $employeeId,
        public int $grossPay,
        public int $loanDeduction,
        public ?string $note,
    ) {}
}
