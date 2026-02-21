<?php

declare(strict_types=1);

namespace App\Application\UseCases\Expenses;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class CreateExpenseUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
    ) {}

    public function handle(CreateExpenseRequest $req): void
    {
        $this->validate($req);

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $now) {
            DB::table('expenses')->insert([
                'expense_date' => $req->expenseDate,
                'category' => $req->category,
                'amount' => $req->amount,
                'note' => $req->note,
                'created_by_user_id' => $req->actorUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function validate(CreateExpenseRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $req->expenseDate);
        if ($dt === false || $dt->format('Y-m-d') !== $req->expenseDate) {
            throw new \InvalidArgumentException('invalid expense_date format (expected Y-m-d)');
        }

        $cat = trim($req->category);
        if ($cat === '' || strlen($cat) > 64) {
            throw new \InvalidArgumentException('invalid category');
        }

        if ($req->amount < 0) {
            throw new \InvalidArgumentException('amount cannot be negative');
        }

        if ($req->note !== null && strlen($req->note) > 255) {
            throw new \InvalidArgumentException('note too long');
        }
    }
}
