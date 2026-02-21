<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DevSampleAllSeeder extends Seeder
{
    public function run(): void
    {
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