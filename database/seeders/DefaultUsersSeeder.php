<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        $adminPassword = (string) env('DEFAULT_ADMIN_PASSWORD', '');
        if ($adminPassword === '') {
            $adminPassword = Str::password(16);
        }

        $cashierPassword = (string) env('DEFAULT_CASHIER_PASSWORD', '');
        if ($cashierPassword === '') {
            $cashierPassword = Str::password(16);
        }

        User::updateOrCreate(
            ['email' => 'bos@local.test'],
            [
                'name' => 'bos',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make($adminPassword),
            ],
        );

        User::updateOrCreate(
            ['email' => 'kasir@local.test'],
            [
                'name' => 'kasir',
                'role' => User::ROLE_CASHIER,
                'password' => Hash::make($cashierPassword),
            ],
        );

        // Optional: tampilkan sekali di console saat seeding
        $this->command?->warn('DEFAULT ADMIN LOGIN: name=bos password='.$adminPassword);
        $this->command?->warn('DEFAULT CASHIER LOGIN: name=kasir password='.$cashierPassword);
    }
}
