<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class CreateEmployeeLoanUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
    ) {}

    public function handle(CreateEmployeeLoanRequest $req): void
    {
        $this->validate($req);

        $now = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $now) {
            $emp = DB::table('employees')->where('id', $req->employeeId)->first(['id']);
            if ($emp === null) {
                throw new \InvalidArgumentException('employee not found');
            }

            DB::table('employee_loans')->insert([
                'employee_id' => $req->employeeId,
                'loan_date' => $req->loanDate,
                'amount' => $req->amount,
                'outstanding_amount' => $req->amount,
                'note' => $req->note,
                'created_by_user_id' => $req->actorUserId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    private function validate(CreateEmployeeLoanRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if ($req->employeeId <= 0) {
            throw new \InvalidArgumentException('invalid employee id');
        }
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $req->loanDate);
        if ($dt === false || $dt->format('Y-m-d') !== $req->loanDate) {
            throw new \InvalidArgumentException('invalid loan_date format (expected Y-m-d)');
        }
        if ($req->amount <= 0) {
            throw new \InvalidArgumentException('amount must be > 0');
        }
        if ($req->note !== null && strlen($req->note) > 255) {
            throw new \InvalidArgumentException('note too long');
        }
    }
}
