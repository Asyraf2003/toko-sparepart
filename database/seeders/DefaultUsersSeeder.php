<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DefaultUsersSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'Admin',
                'role' => User::ROLE_ADMIN,
                'password' => Hash::make('12345678'),
            ],
        );

        User::updateOrCreate(
            ['email' => 'cashier@local.test'],
            [
                'name' => 'Cashier',
                'role' => User::ROLE_CASHIER,
                'password' => Hash::make('12345678'),
            ],
        );
    }
}
