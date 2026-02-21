<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultUsersSeeder::class,
            DefaultEmployeesSeeder::class,
        ]);

        if (app()->environment('local', 'testing')) {
            $this->call([
                DevSampleProductsSeeder::class,
                DevSamplePurchasesSeeder::class,
                DevSampleExpensesSeeder::class,
                DevSampleEmployeeLoansSeeder::class,
                DevSamplePayrollSeeder::class,
                DevSampleTransactionsSeeder::class,
                DevEnsureInventoryStocksSeeder::class,
            ]);
        }
    }
}