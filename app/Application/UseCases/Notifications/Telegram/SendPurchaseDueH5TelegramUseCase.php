<?php

declare(strict_types=1);

namespace App\Application\UseCases\Notifications\Telegram;

use App\Application\Ports\Services\ClockPort;
use App\Application\Services\TelegramOpsMessage;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class SendPurchaseDueH5TelegramUseCase
{
    public function __construct(
        private ClockPort $clock,
    ) {}

    public function handle(): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        $dueEnabled = (bool) config('services.telegram_ops.purchase_due_enabled', true);
        if (! $enabled || ! $dueEnabled) {
            return;
        }

        $chatIds = $this->parseChatIds((string) config('services.telegram_ops.chat_ids', ''));
        if (count($chatIds) === 0) {
            return;
        }

        $today = CarbonImmutable::instance($this->clock->now());
        $targetDue = $today->addDays(5)->toDateString();
        $sendDate = $today->toDateString();

        $rows = DB::table('purchase_invoices')
            ->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            })
            ->whereNotNull('due_date')
            ->where('due_date', $targetDue)
            ->orderBy('due_date')
            ->orderBy('supplier_name')
            ->limit(50)
            ->get(['id', 'supplier_name', 'no_faktur', 'tgl_kirim', 'due_date', 'grand_total']);

        if ($rows->count() === 0) {
            return;
        }

        $tpl = TelegramOpsMessage::fromConfig();
        $text = $tpl->purchaseDueH5Digest($targetDue, $rows->all());

        foreach ($chatIds as $chatId) {
            $dedupKey = 'purchase_due_digest:'.$sendDate.':'.$chatId;
            if (DB::table('notification_states')->where('key', $dedupKey)->exists()) {
                continue;
            }

            SendTelegramMessageJob::dispatch(
                chatId: $chatId,
                text: $text,
                dedupKey: $dedupKey,
                metaJson: json_encode(['type' => 'purchase_due_h5', 'send_date' => $sendDate, 'due_date' => $targetDue], JSON_THROW_ON_ERROR),
            )->onQueue('notifications');
        }
    }

    /**
     * @return list<string>
     */
    private function parseChatIds(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        $out = [];
        foreach ($parts as $p) {
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }
}
