<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

final readonly class CreateEmployeeLoanRequest
{
    public function __construct(
        public int $actorUserId,
        public int $employeeId,
        public string $loanDate, // Y-m-d
        public int $amount, // rupiah integer
        public ?string $note,
    ) {}
}
