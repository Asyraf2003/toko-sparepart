<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DefaultUsersSeeder::class,
            DefaultEmployeesSeeder::class,
            DevSampleProductsSeeder::class,

            DevSamplePurchasesSeeder::class,

            DevSampleExpensesSeeder::class,
            DevSampleEmployeeLoansSeeder::class,
            DevSamplePayrollSeeder::class,
        ]);
    }
}
