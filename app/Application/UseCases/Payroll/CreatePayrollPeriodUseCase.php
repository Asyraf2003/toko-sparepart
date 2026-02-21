<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use Illuminate\Support\Facades\DB;

final readonly class CreatePayrollPeriodUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
    ) {}

    public function handle(CreatePayrollPeriodRequest $req): void
    {
        $this->validateRequest($req);

        $now = $this->clock->now();
        $nowStr = $now->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $nowStr) {
            // prevent duplicate (also enforced by unique index)
            $exists = DB::table('payroll_periods')
                ->where('week_start', $req->weekStart)
                ->where('week_end', $req->weekEnd)
                ->exists();
            if ($exists) {
                throw new \InvalidArgumentException('payroll period already exists');
            }

            $periodId = (int) DB::table('payroll_periods')->insertGetId([
                'week_start' => $req->weekStart,
                'week_end' => $req->weekEnd,
                'note' => $req->note,
                'loan_deductions_applied_at' => null,
                'created_by_user_id' => $req->actorUserId,
                'created_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);

            // insert lines
            foreach ($req->lines as $line) {
                $emp = DB::table('employees')->where('id', $line->employeeId)->first(['id']);
                if ($emp === null) {
                    throw new \InvalidArgumentException('employee not found');
                }

                $net = $line->grossPay - $line->loanDeduction;
                if ($net < 0) {
                    throw new \InvalidArgumentException('net pay cannot be negative');
                }

                DB::table('payroll_lines')->insert([
                    'payroll_period_id' => $periodId,
                    'employee_id' => $line->employeeId,
                    'gross_pay' => $line->grossPay,
                    'loan_deduction' => $line->loanDeduction,
                    'net_paid' => $net,
                    'note' => $line->note,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);
            }

            // apply loan deductions immediately (FIFO), reject if deduction > outstanding (K3=A)
            $this->applyLoanDeductionsForPeriodId($periodId, $nowStr);

            // mark applied
            DB::table('payroll_periods')->where('id', $periodId)->update([
                'loan_deductions_applied_at' => $nowStr,
                'updated_at' => $nowStr,
            ]);
        });
    }

    private function validateRequest(CreatePayrollPeriodRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }

        $ws = \DateTimeImmutable::createFromFormat('Y-m-d', $req->weekStart);
        $we = \DateTimeImmutable::createFromFormat('Y-m-d', $req->weekEnd);
        if ($ws === false || $ws->format('Y-m-d') !== $req->weekStart) {
            throw new \InvalidArgumentException('invalid week_start format (expected Y-m-d)');
        }
        if ($we === false || $we->format('Y-m-d') !== $req->weekEnd) {
            throw new \InvalidArgumentException('invalid week_end format (expected Y-m-d)');
        }

        // K1: Senin-Sabtu (Minggu libur)
        // ISO-8601 numeric: 1=Mon, ... 6=Sat, 7=Sun
        if ($ws->format('N') !== '1') {
            throw new \InvalidArgumentException('week_start must be Monday');
        }
        if ($we->format('N') !== '6') {
            throw new \InvalidArgumentException('week_end must be Saturday');
        }
        $diffDays = (int) $ws->diff($we)->format('%a');
        if ($diffDays !== 5) {
            throw new \InvalidArgumentException('payroll week must be Monday-Saturday (6 days)');
        }

        if ($req->note !== null && strlen($req->note) > 255) {
            throw new \InvalidArgumentException('note too long');
        }

        if (count($req->lines) === 0) {
            throw new \InvalidArgumentException('lines required');
        }

        foreach ($req->lines as $line) {
            if (! $line instanceof CreatePayrollPeriodLine) {
                throw new \InvalidArgumentException('invalid line payload');
            }
            if ($line->employeeId <= 0) {
                throw new \InvalidArgumentException('invalid employee id');
            }
            if ($line->grossPay < 0) {
                throw new \InvalidArgumentException('gross_pay cannot be negative');
            }
            if ($line->loanDeduction < 0) {
                throw new \InvalidArgumentException('loan_deduction cannot be negative');
            }
            if ($line->loanDeduction > $line->grossPay) {
                throw new \InvalidArgumentException('loan_deduction cannot exceed gross_pay');
            }
            if ($line->note !== null && strlen($line->note) > 255) {
                throw new \InvalidArgumentException('line note too long');
            }
        }
    }

    private function applyLoanDeductionsForPeriodId(int $periodId, string $nowStr): void
    {
        $lines = DB::table('payroll_lines')
            ->where('payroll_period_id', $periodId)
            ->where('loan_deduction', '>', 0)
            ->lockForUpdate()
            ->get(['employee_id', 'loan_deduction']);

        foreach ($lines as $pl) {
            $employeeId = (int) $pl->employee_id;
            $deduction = (int) $pl->loan_deduction;

            if ($deduction <= 0) {
                continue;
            }

            // FIFO loans: oldest first
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

            // K3: Reject if deduction > outstanding
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

                if ($out <= 0) {
                    continue;
                }

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
    }
}
