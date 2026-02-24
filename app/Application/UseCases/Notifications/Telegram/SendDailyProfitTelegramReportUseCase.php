<?php

declare(strict_types=1);

namespace App\Application\UseCases\Notifications\Telegram;

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use App\Application\Ports\Services\ClockPort;
use App\Infrastructure\Notifications\Telegram\SendTelegramMessageJob;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class SendDailyProfitTelegramReportUseCase
{
    public function __construct(
        private ClockPort $clock,
        private ProfitReportQueryPort $profit,
    ) {}

    public function handle(): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        $profitEnabled = (bool) config('services.telegram_ops.profit_enabled', true);
        if (! $enabled || ! $profitEnabled) {
            return;
        }

        $chatIds = $this->parseChatIds((string) config('services.telegram_ops.chat_ids', ''));
        if (count($chatIds) === 0) {
            return;
        }

        $today = CarbonImmutable::instance($this->clock->now())->toDateString();

        foreach ($chatIds as $chatId) {
            $dedupKey = 'profit_daily:'.$today.':'.$chatId;
            if (DB::table('notification_states')->where('key', $dedupKey)->exists()) {
                continue;
            }

            $res = $this->profit->aggregate($today, $today, 'daily');
            $row = $res->rows[0] ?? null;

            $text = $this->buildText($today, $row);

            SendTelegramMessageJob::dispatch(
                chatId: $chatId,
                text: $text,
                dedupKey: $dedupKey,
                metaJson: json_encode(['type' => 'profit_daily', 'date' => $today], JSON_THROW_ON_ERROR),
            )->onQueue('notifications');
        }
    }

    private function buildText(string $date, mixed $row): string
    {
        if ($row === null) {
            return implode("\n", [
                '📈 PROFIT HARIAN',
                'Tanggal: '.$date,
                'Data: (kosong)',
            ]);
        }

        $money = fn (int $v): string => number_format($v, 0, ',', '.');

        $lines = [
            '📈 PROFIT HARIAN',
            'Tanggal: '.$date,
            'Revenue: Rp '.$money((int) $row->revenueTotal),
            'COGS: Rp '.$money((int) $row->cogsTotal),
            'Expenses: Rp '.$money((int) $row->expensesTotal),
            'Payroll: Rp '.$money((int) $row->payrollGross),
            'Net: Rp '.$money((int) $row->netProfit),
        ];

        $missing = (int) $row->missingCogsQty;
        if ($missing > 0) {
            $lines[] = '⚠️ Missing COGS Qty: '.$missing;
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
