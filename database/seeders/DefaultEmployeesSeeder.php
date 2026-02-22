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
            'Dimas',
            'Rudi',
            'Wahyu',
            'Agus',
            'Putra',
        ];

        foreach ($names as $name) {
            $exists = DB::table('employees')->where('name', $name)->exists();

            if (! $exists) {
                DB::table('employees')->insert([
                    'name' => $name,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                continue;
            }

            DB::table('employees')->where('name', $name)->update([
                'is_active' => 1,
                'updated_at' => now(),
            ]);
        }
    }
}
