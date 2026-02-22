<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

final class DevSampleAllSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // catalog + inventory dulu (purchases butuh ini)
            DevSampleProductsSeeder::class,
            DevEnsureInventoryStocksSeeder::class,

            // purchasing dulu biar avg_cost/stock kebentuk sebelum transaksi (COGS freeze)
            DevSamplePurchasesSeeder::class,

            // expenses/loans/payroll (admin UI)
            DevSampleExpensesSeeder::class,
            DevSampleEmployeeLoansSeeder::class,
            DevSamplePayrollSeeder::class,

            // terakhir transaksi (pakai avg_cost untuk freeze COGS + cash fields enterprise)
            DevSampleTransactionsSeeder::class,
        ]);
    }
}
