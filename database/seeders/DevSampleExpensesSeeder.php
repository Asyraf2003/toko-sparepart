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
        // idempotency: if a known seed row exists, skip
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
        $items = [
            ['days' => 0,  'cat' => 'listrik',  'amount' => 150000, 'note' => 'seed demo expense (m6)'],
            ['days' => 0,  'cat' => 'konsumsi', 'amount' => 80000,  'note' => 'seed demo expense (m6)'],
            ['days' => 1,  'cat' => 'sewa',     'amount' => 500000, 'note' => 'seed demo expense (m6)'],
            ['days' => 2,  'cat' => 'alat',     'amount' => 120000, 'note' => 'seed demo expense (m6)'],
            ['days' => 3,  'cat' => 'internet', 'amount' => 95000,  'note' => 'seed demo expense (m6)'],
            ['days' => 5,  'cat' => 'oli',      'amount' => 60000,  'note' => 'seed demo expense (m6)'],
            ['days' => 7,  'cat' => 'kebersihan', 'amount' => 45000, 'note' => 'seed demo expense (m6)'],
            ['days' => 10, 'cat' => 'transport', 'amount' => 70000,  'note' => 'seed demo expense (m6)'],
        ];

        foreach ($items as $it) {
            $date = $base->copy()->subDays((int) $it['days'])->format('Y-m-d');

            $uc->handle(new CreateExpenseRequest(
                actorUserId: (int) $adminId,
                expenseDate: $date,
                category: (string) $it['cat'],
                amount: (int) $it['amount'],
                note: (string) $it['note'],
            ));
        }
    }
}
