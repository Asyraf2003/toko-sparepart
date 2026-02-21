<?php

declare(strict_types=1);

namespace App\Application\UseCases\Expenses;

final readonly class CreateExpenseRequest
{
    public function __construct(
        public int $actorUserId,
        public string $expenseDate, // Y-m-d
        public string $category,
        public int $amount, // rupiah integer
        public ?string $note,
    ) {}
}
