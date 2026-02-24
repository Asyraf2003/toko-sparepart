<?php

use App\Infrastructure\Notifications\Telegram\DownloadTelegramPaymentProofJob;
use Database\Seeders\DefaultUsersSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches download proof job when awaiting upload and document sent', function () {
    config()->set('services.telegram_ops.enabled', true);
    config()->set('services.telegram_ops.webhook_secret', 'secret123');

    $this->seed(DefaultUsersSeeder::class);
    $admin = \App\Models\User::query()->where('role', \App\Models\User::ROLE_ADMIN)->first();
    expect($admin)->not->toBeNull();

    // link chat to admin
    DB::table('telegram_links')->insert([
        'user_id' => $admin->id,
        'chat_id' => '999',
        'linked_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // create invoice
    $invoiceId = (int) DB::table('purchase_invoices')->insertGetId([
        'supplier_name' => 'Supplier X',
        'no_faktur' => 'FAK-100',
        'tgl_kirim' => '2026-02-01',
        'due_date' => '2026-03-01',
        'payment_status' => 'UNPAID',
        'grand_total' => 10000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // set conversation awaiting upload
    DB::table('telegram_conversations')->insert([
        'chat_id' => '999',
        'state' => 'AWAIT_PROOF_UPLOAD',
        'data_json' => json_encode(['purchase_invoice_id' => $invoiceId, 'no_faktur' => 'FAK-100'], JSON_THROW_ON_ERROR),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Queue::fake();

    $payload = [
        'message' => [
            'message_id' => 12345,
            'chat' => ['id' => '999'],
            'document' => [
                'file_id' => 'FILEID123',
                'file_name' => 'proof.pdf',
            ],
        ],
    ];

    $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret123')
        ->postJson('/telegram/webhook', $payload)
        ->assertOk();

    Queue::assertPushed(DownloadTelegramPaymentProofJob::class);
});
