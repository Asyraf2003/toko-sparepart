<?php

declare(strict_types=1);

namespace Tests\Feature\Cashier;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class TransactionsTodayPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/cashier/transactions/today')->assertRedirect('/login');
    }

    public function test_admin_forbidden(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin_cashier_page@local.test',
            'role' => User::ROLE_ADMIN,
            'password' => '12345678',
        ]);

        $this->actingAs($admin)->get('/cashier/transactions/today')->assertStatus(403);
    }

    public function test_cashier_can_view(): void
    {
        $cashier = User::query()->create([
            'name' => 'Cashier',
            'email' => 'cashier_cashier_page@local.test',
            'role' => User::ROLE_CASHIER,
            'password' => '12345678',
        ]);

        $this->actingAs($cashier)->get('/cashier/transactions/today')->assertStatus(200);
    }
}
