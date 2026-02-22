<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Application\UseCases\Expenses\CreateExpenseRequest;
use App\Application\UseCases\Expenses\CreateExpenseUseCase;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DevSampleExpensesSeeder extends Seeder
{
    public function run(): void
    {
        // idempotency: jika seed note ada, skip
        if (DB::table('expenses')->where('note', 'seed demo expense (m6)')->exists()) {
            return;
        }

        $adminId = User::query()
            ->where('role', User::ROLE_ADMIN)
            ->orderBy('id')
            ->value('id');

        if ($adminId === null) {
            throw new \RuntimeException('Admin user not found. Ensure DefaultUsersSeeder runs first.');
        }

        /** @var CreateExpenseUseCase $uc */
        $uc = app(CreateExpenseUseCase::class);

        $base = now();

        $categories = [
            'listrik',
            'konsumsi',
            'sewa',
            'alat',
            'internet',
            'transport',
            'kebersihan',
            'oli',
            'air',
            'parkir',
        ];

        // 25 expense acak dalam 21 hari terakhir
        for ($i = 0; $i < 25; $i++) {
            $days = random_int(0, 21);
            $date = $base->copy()->subDays($days)->format('Y-m-d');

            $cat = $categories[array_rand($categories)];
            $amount = random_int(20000, 50000);
            // rapihin kelipatan 1000
            $amount = (int) (round($amount / 1000) * 1000);

            $uc->handle(new CreateExpenseRequest(
                actorUserId: (int) $adminId,
                expenseDate: $date,
                category: (string) $cat,
                amount: (int) $amount,
                note: 'seed demo expense (m6)',
            ));
        }
    }
}
