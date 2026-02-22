<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Payroll\CreateEmployeeLoanRequest;
use App\Application\UseCases\Payroll\CreateEmployeeLoanUseCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevSampleEmployeeLoansSeeder extends Seeder
{
    public function run(): void
    {
        // idempotency
        if (DB::table('employee_loans')->where('note', 'seed demo loan (m6)')->exists()) {
            return;
        }

        $adminId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            throw new \RuntimeException('Admin user not found. Ensure DefaultUsersSeeder runs first.');
        }

        /** @var CreateEmployeeLoanUseCase $uc */
        $uc = app(CreateEmployeeLoanUseCase::class);

        // sekarang employees 10, ambil 5 untuk variasi
        $employees = DB::table('employees')
            ->where('is_active', true)
            ->orderBy('id')
            ->limit(5)
            ->get(['id']);

        if ($employees->count() === 0) {
            throw new \RuntimeException('No employees found. Ensure DefaultEmployeesSeeder runs first.');
        }

        $base = now();

        foreach ($employees as $idx => $e) {
            $employeeId = (int) $e->id;

            // 2 loans per employee
            $uc->handle(new CreateEmployeeLoanRequest(
                actorUserId: (int) $adminId,
                employeeId: $employeeId,
                loanDate: $base->copy()->subDays(25 + ($idx * 2))->format('Y-m-d'),
                amount: 100000 + ($idx * 25000),
                note: 'seed demo loan (m6)',
            ));

            $uc->handle(new CreateEmployeeLoanRequest(
                actorUserId: (int) $adminId,
                employeeId: $employeeId,
                loanDate: $base->copy()->subDays(12 + ($idx * 2))->format('Y-m-d'),
                amount: 75000 + ($idx * 15000),
                note: 'seed demo loan (m6)',
            ));
        }
    }
}
