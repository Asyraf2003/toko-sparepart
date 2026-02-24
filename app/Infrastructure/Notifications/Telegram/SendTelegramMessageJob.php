<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use App\Application\Ports\Services\TelegramSenderPort;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

final class SendTelegramMessageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly string $text,
        public readonly string $dedupKey,
        public readonly ?string $metaJson = null,
    ) {}

    public function handle(TelegramSenderPort $tg): void
    {
        // Job-level idempotency: if already sent, do nothing.
        if (DB::table('notification_states')->where('key', $this->dedupKey)->exists()) {
            return;
        }

        try {
            $tg->sendMessage($this->chatId, $this->text);
        } catch (TelegramRateLimitedException $e) {
            // Respect Telegram retry_after to avoid silent drops.
            $this->release($e->retryAfterSeconds());

            return;
        }

        DB::table('notification_states')->insertOrIgnore([
            'key' => $this->dedupKey,
            'sent_at' => now(),
            'meta_json' => $this->metaJson,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
