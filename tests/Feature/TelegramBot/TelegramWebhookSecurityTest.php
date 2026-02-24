<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('rejects webhook without secret header', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.webhook_secret', 'secret123');

    $this->postJson('/telegram/webhook', ['message' => ['chat' => ['id' => '1'], 'text' => '/start']])
        ->assertStatus(403);
});
