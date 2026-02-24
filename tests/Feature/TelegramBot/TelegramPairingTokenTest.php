<?php

use Database\Seeders\DefaultUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('creates a pairing token from admin page', function () {
    $this->seed(DefaultUsersSeeder::class);

    $admin = \App\Models\User::query()->where('role', \App\Models\User::ROLE_ADMIN)->first();
    expect($admin)->not->toBeNull();

    $this->actingAs($admin)
        ->post('/admin/telegram/pairing-token')
        ->assertRedirect();

    $count = DB::table('telegram_pairing_tokens')->where('user_id', $admin->id)->count();
    expect($count)->toBeGreaterThan(0);
});
