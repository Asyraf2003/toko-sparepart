<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Payroll\CreatePayrollPeriodLine;
use App\Application\UseCases\Payroll\CreatePayrollPeriodRequest;
use App\Application\UseCases\Payroll\CreatePayrollPeriodUseCase;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevSamplePayrollSeeder extends Seeder
{
    public function run(): void
    {
        $tz = (string) config('app.timezone', 'UTC');
        $today = CarbonImmutable::now($tz);

        // Mon-Sat
        $weekStart = $today->startOfWeek(CarbonImmutable::MONDAY);
        $weekEnd = $weekStart->addDays(5);

        $weekStartStr = $weekStart->format('Y-m-d');
        $weekEndStr = $weekEnd->format('Y-m-d');

        // idempotency
        if (DB::table('payroll_periods')->where('week_start', $weekStartStr)->where('week_end', $weekEndStr)->exists()) {
            return;
        }

        $adminId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            throw new \RuntimeException('Admin user not found. Ensure DefaultUsersSeeder runs first.');
        }

        /** @var CreatePayrollPeriodUseCase $uc */
        $uc = app(CreatePayrollPeriodUseCase::class);

        // sekarang employees 10 -> pakai semua biar UI payroll penuh
        $employees = DB::table('employees')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(10)
            ->get(['id', 'name']);

        if ($employees->count() === 0) {
            throw new \RuntimeException('No employees found.');
        }

        // Outstanding per employee (sum)
        $outstanding = DB::table('employee_loans')
            ->selectRaw('employee_id, SUM(outstanding_amount) AS outstanding')
            ->where('outstanding_amount', '>', 0)
            ->groupBy('employee_id')
            ->pluck('outstanding', 'employee_id');

        $lines = [];

        foreach ($employees as $i => $e) {
            $employeeId = (int) $e->id;

            // gross bertingkat
            $gross = 300000 + ($i * 25000);

            // Deduct untuk 4 employee pertama kalau ada outstanding
            $out = (int) ($outstanding[$employeeId] ?? 0);
            $deduction = 0;

            if ($i < 4 && $out > 0) {
                $deduction = min(150000, $out);
            }

            $lines[] = new CreatePayrollPeriodLine(
                employeeId: $employeeId,
                grossPay: $gross,
                loanDeduction: $deduction,
                note: 'seed demo payroll (m6)',
            );
        }

        $uc->handle(new CreatePayrollPeriodRequest(
            actorUserId: (int) $adminId,
            weekStart: $weekStartStr,
            weekEnd: $weekEndStr,
            note: 'seed demo payroll period (m6)',
            lines: $lines,
        ));
    }
}
