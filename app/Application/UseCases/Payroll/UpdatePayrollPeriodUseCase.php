<?php

declare(strict_types=1);

namespace App\Application\UseCases\Payroll;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePayrollPeriodUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(UpdatePayrollPeriodRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if ($req->payrollPeriodId <= 0) {
            throw new \InvalidArgumentException('invalid payroll period id');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $ws = \DateTimeImmutable::createFromFormat('Y-m-d', $req->weekStart);
        $we = \DateTimeImmutable::createFromFormat('Y-m-d', $req->weekEnd);
        if ($ws === false || $ws->format('Y-m-d') !== $req->weekStart) {
            throw new \InvalidArgumentException('invalid week_start format (expected Y-m-d)');
        }
        if ($we === false || $we->format('Y-m-d') !== $req->weekEnd) {
            throw new \InvalidArgumentException('invalid week_end format (expected Y-m-d)');
        }
        if ($ws > $we) {
            throw new \InvalidArgumentException('week_start must be <= week_end');
        }
        // policy mingguan: Senin (1) - Sabtu (6)
        if ($ws->format('N') !== '1') {
            throw new \InvalidArgumentException('week_start must be Monday');
        }
        if ($we->format('N') !== '6') {
            throw new \InvalidArgumentException('week_end must be Saturday');
        }

        $nowStr = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $reason, $nowStr): void {
            $period = DB::table('payroll_periods')
                ->where('id', $req->payrollPeriodId)
                ->lockForUpdate()
                ->first([
                    'id',
                    'week_start',
                    'week_end',
                    'note',
                    'loan_deductions_applied_at',
                ]);

            if ($period === null) {
                throw new \InvalidArgumentException('payroll period not found');
            }

            $locked = $period->loan_deductions_applied_at !== null;

            $before = [
                'week_start' => (string) $period->week_start,
                'week_end' => (string) $period->week_end,
                'note' => $period->note !== null ? (string) $period->note : null,
                'loan_deductions_applied_at' => $period->loan_deductions_applied_at !== null ? (string) $period->loan_deductions_applied_at : null,
            ];

            if ($locked) {
                // POLICY: sudah applied -> tidak boleh ubah periode/lines (agar laporan & loan potongan konsisten)
                $note = $req->note !== null ? trim((string) $req->note) : null;

                DB::table('payroll_periods')
                    ->where('id', $req->payrollPeriodId)
                    ->update([
                        'note' => $note,
                        'updated_at' => $nowStr,
                    ]);

                $after = $before;
                $after['note'] = $note;

                $this->audit->append(new AuditEntry(
                    actorId: $req->actorUserId,
                    actorRole: null,
                    entityType: 'PayrollPeriod',
                    entityId: $req->payrollPeriodId,
                    action: 'PAYROLL_PERIOD_UPDATE_LOCKED',
                    reason: $reason,
                    before: $before,
                    after: $after,
                    meta: [
                        'policy' => 'locked: header note only',
                    ],
                ));

                return;
            }

            // unlocked: boleh edit header + lines
            /** @var list<UpdatePayrollPeriodLine> $lines */
            $lines = $req->lines;

            if (count($lines) === 0) {
                throw new \InvalidArgumentException('lines required');
            }

            // validate & ensure employee ids unique
            $employeeIds = [];
            foreach ($lines as $line) {
                if (! $line instanceof UpdatePayrollPeriodLine) {
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
                if ($line->grossPay === 0 && $line->loanDeduction === 0) {
                    throw new \InvalidArgumentException('zero line is not allowed');
                }
                $employeeIds[] = $line->employeeId;
            }

            $employeeIds = array_values(array_unique($employeeIds));
            $existingEmployees = DB::table('employees')->whereIn('id', $employeeIds)->count();
            if ($existingEmployees !== count($employeeIds)) {
                throw new \InvalidArgumentException('one or more employees not found');
            }

            DB::table('payroll_periods')
                ->where('id', $req->payrollPeriodId)
                ->update([
                    'week_start' => $req->weekStart,
                    'week_end' => $req->weekEnd,
                    'note' => $req->note !== null ? trim((string) $req->note) : null,
                    'updated_at' => $nowStr,
                ]);

            DB::table('payroll_lines')
                ->where('payroll_period_id', $req->payrollPeriodId)
                ->delete();

            foreach ($lines as $line) {
                $net = $line->grossPay - $line->loanDeduction;
                DB::table('payroll_lines')->insert([
                    'payroll_period_id' => $req->payrollPeriodId,
                    'employee_id' => $line->employeeId,
                    'gross_pay' => $line->grossPay,
                    'loan_deduction' => $line->loanDeduction,
                    'net_paid' => $net,
                    'note' => $line->note !== null ? trim((string) $line->note) : null,
                    'created_at' => $nowStr,
                    'updated_at' => $nowStr,
                ]);
            }

            $after = [
                'week_start' => $req->weekStart,
                'week_end' => $req->weekEnd,
                'note' => $req->note !== null ? trim((string) $req->note) : null,
                'loan_deductions_applied_at' => null,
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: null,
                entityType: 'PayrollPeriod',
                entityId: $req->payrollPeriodId,
                action: 'PAYROLL_PERIOD_UPDATE',
                reason: $reason,
                before: $before,
                after: $after,
                meta: [
                    'policy' => 'unlocked: header + lines replaced',
                    'line_count' => count($lines),
                ],
            ));
        });
    }
}
