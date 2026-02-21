<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class ApplyLoanDeductionUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
    ) {}

    public function handle(ApplyLoanDeductionRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if ($req->payrollPeriodId <= 0) {
            throw new \InvalidArgumentException('invalid payroll period id');
        }

        $nowStr = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $nowStr) {
            $period = DB::table('payroll_periods')
                ->where('id', $req->payrollPeriodId)
                ->lockForUpdate()
                ->first(['id', 'loan_deductions_applied_at']);

            if ($period === null) {
                throw new \InvalidArgumentException('payroll period not found');
            }
            if ($period->loan_deductions_applied_at !== null) {
                throw new \InvalidArgumentException('loan deductions already applied');
            }

            // apply FIFO (same logic as CreatePayrollPeriodUseCase)
            $lines = DB::table('payroll_lines')
                ->where('payroll_period_id', $req->payrollPeriodId)
                ->where('loan_deduction', '>', 0)
                ->lockForUpdate()
                ->get(['employee_id', 'loan_deduction']);

            foreach ($lines as $pl) {
                $employeeId = (int) $pl->employee_id;
                $deduction = (int) $pl->loan_deduction;

                $loans = DB::table('employee_loans')
                    ->where('employee_id', $employeeId)
                    ->where('outstanding_amount', '>', 0)
                    ->orderBy('loan_date')
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get(['id', 'outstanding_amount']);

                $totalOutstanding = 0;
                foreach ($loans as $ln) {
                    $totalOutstanding += (int) $ln->outstanding_amount;
                }

                if ($deduction > $totalOutstanding) {
                    throw new \InvalidArgumentException('loan deduction exceeds total outstanding');
                }

                $remaining = $deduction;

                foreach ($loans as $ln) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $loanId = (int) $ln->id;
                    $out = (int) $ln->outstanding_amount;

                    $take = $remaining <= $out ? $remaining : $out;
                    $newOut = $out - $take;

                    DB::table('employee_loans')->where('id', $loanId)->update([
                        'outstanding_amount' => $newOut,
                        'updated_at' => $nowStr,
                    ]);

                    $remaining -= $take;
                }

                if ($remaining !== 0) {
                    throw new \RuntimeException('unexpected remaining deduction');
                }
            }

            DB::table('payroll_periods')->where('id', $req->payrollPeriodId)->update([
                'loan_deductions_applied_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);
        });
    }
}
