<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DefaultEmployeesSeeder extends Seeder
{
    public function run(): void
    {
        $names = [
            'Rian',
            'Aldi',
            'Bima',
            'Fajar',
            'Sandi',
        ];

        foreach ($names as $name) {
            DB::table('employees')->insert([
                'name' => $name,
                'is_active' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
