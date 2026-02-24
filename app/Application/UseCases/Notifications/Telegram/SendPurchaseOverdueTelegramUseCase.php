<?php

declare(strict_types=1);

namespace App\Application\UseCases\Notifications\Telegram;

use App\Application\Ports\Services\ClockPort;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class SendPurchaseOverdueTelegramUseCase
{
    public function __construct(
        private ClockPort $clock,
    ) {}

    public function handle(): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        $overdueEnabled = (bool) config('services.telegram_ops.purchase_overdue_enabled', true);
        if (! $enabled || ! $overdueEnabled) {
            return;
        }

        $chatIds = $this->parseChatIds((string) config('services.telegram_ops.chat_ids', ''));
        if (count($chatIds) === 0) {
            return;
        }

        $today = CarbonImmutable::instance($this->clock->now())->startOfDay();
        $todayStr = $today->toDateString();

        $rows = DB::table('purchase_invoices')
            ->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            })
            ->whereNotNull('due_date')
            ->where('due_date', '<', $todayStr)
            ->orderBy('due_date')
            ->orderBy('supplier_name')
            ->limit(50)
            ->get(['id', 'supplier_name', 'no_faktur', 'tgl_kirim', 'due_date', 'grand_total']);

        if ($rows->count() === 0) {
            return;
        }

        $text = $this->buildDigest($todayStr, $rows->all());

        foreach ($chatIds as $chatId) {
            $dedupKey = 'purchase_overdue_digest:'.$todayStr.':'.$chatId;
            if (DB::table('notification_states')->where('key', $dedupKey)->exists()) {
                continue;
            }

            SendTelegramMessageJob::dispatch(
                chatId: $chatId,
                text: $text,
                dedupKey: $dedupKey,
                metaJson: json_encode(['type' => 'purchase_overdue', 'date' => $todayStr], JSON_THROW_ON_ERROR),
            )->onQueue('notifications');
        }
    }

    private function buildDigest(string $today, array $rows): string
    {
        $money = fn (int $v): string => number_format($v, 0, ',', '.');

        $lines = [
            '🚨 OVERDUE PEMBELIAN',
            'Tanggal: '.$today,
            'Jumlah: '.count($rows),
            '',
        ];

        foreach ($rows as $r) {
            $lines[] = implode(' | ', [
                (string) $r->no_faktur,
                (string) $r->supplier_name,
                'Due: '.(string) $r->due_date,
                'Total: Rp '.$money((int) $r->grand_total),
            ]);
        }

        return implode("\n", $lines);
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