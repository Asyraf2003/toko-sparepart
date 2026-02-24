<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Telegram;

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TelegramSenderPort;
use App\Application\Services\TelegramOpsMessage;
use App\Infrastructure\Notifications\Telegram\DownloadTelegramPaymentProofJob;
use App\Infrastructure\Notifications\Telegram\SendTelegramMenuJob;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final readonly class TelegramWebhookController
{
    public function __construct(
        private TelegramSenderPort $tg,
        private ProfitReportQueryPort $profit,
        private ClockPort $clock,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $expected = (string) config('services.telegram_ops.webhook_secret', '');
        $got = (string) $request->header('X-Telegram-Bot-Api-Secret-Token', '');
        if ($expected === '' || ! hash_equals($expected, $got)) {
            return response()->json(['ok' => false], 403);
        }

        $enabled = (bool) config('services.telegram_ops.enabled', false);
        if (! $enabled) {
            return response()->json(['ok' => true]);
        }

        $payload = $request->all();
        $tpl = TelegramOpsMessage::fromConfig();

        if (isset($payload['message'])) {
            $this->handleMessage($payload['message'], $tpl);

            return response()->json(['ok' => true]);
        }

        if (isset($payload['callback_query'])) {
            $this->handleCallback($payload['callback_query'], $tpl);

            return response()->json(['ok' => true]);
        }

        return response()->json(['ok' => true]);
    }

    private function handleCallback(array $cb, TelegramOpsMessage $tpl): void
    {
        $chatId = (string) ($cb['message']['chat']['id'] ?? '');
        $data = (string) ($cb['data'] ?? '');

        if ($chatId === '') {
            return;
        }

        if (! $this->isLinkedAdminChat($chatId)) {
            $this->tg->sendMessage($chatId, $tpl->botNotLinked());

            return;
        }

        if ($data === 'menu_unpaid') {
            $this->sendUnpaidList($chatId);

            return;
        }

        if ($data === 'menu_profit') {
            $this->sendProfitLatest($chatId, $tpl);

            return;
        }

        if ($data === 'menu_pay') {
            $this->setConversation($chatId, 'AWAIT_INVOICE_NO', null);
            $this->tg->sendMessage($chatId, $tpl->botAskInvoiceNo());

            return;
        }

        $this->dispatchMenu($chatId, $tpl);
    }

    private function handleMessage(array $msg, TelegramOpsMessage $tpl): void
    {
        $chatId = (string) ($msg['chat']['id'] ?? '');
        if ($chatId === '') {
            return;
        }

        $text = trim((string) ($msg['text'] ?? ''));

        if ($text === '/start') {
            $this->tg->sendMessage($chatId, $tpl->botWelcome());

            return;
        }

        if (str_starts_with($text, '/link')) {
            $this->handleLinkCommand($chatId, $text, $tpl);

            return;
        }

        if (! $this->isLinkedAdminChat($chatId)) {
            $this->tg->sendMessage($chatId, $tpl->botNotLinked());

            return;
        }

        if ($text === '/menu') {
            $this->dispatchMenu($chatId, $tpl);

            return;
        }

        if ($text === '/purchases_unpaid') {
            $this->sendUnpaidList($chatId);

            return;
        }

        if ($text === '/profit_latest') {
            $this->sendProfitLatest($chatId, $tpl);

            return;
        }

        if ($text === '/pay') {
            $this->setConversation($chatId, 'AWAIT_INVOICE_NO', null);
            $this->tg->sendMessage($chatId, $tpl->botAskInvoiceNo());

            return;
        }

        $conv = $this->getConversation($chatId);

        if ($conv !== null && $conv['state'] === 'AWAIT_INVOICE_NO' && $text !== '') {
            $this->handleInvoiceNoInput($chatId, $text, $tpl);

            return;
        }

        if ($conv !== null && $conv['state'] === 'AWAIT_PROOF_UPLOAD') {
            $this->dispatchProofUploadJob($chatId, $msg, $conv['data'], $tpl);

            return;
        }

        $this->tg->sendMessage($chatId, $tpl->botWelcome());
    }

    private function dispatchMenu(string $chatId, TelegramOpsMessage $tpl): void
    {
        SendTelegramMenuJob::dispatch(
            chatId: $chatId,
            text: $tpl->botWelcome(),
            inlineKeyboard: [
                [
                    ['text' => '📦 Unpaid Supplier', 'callback_data' => 'menu_unpaid'],
                ],
                [
                    ['text' => '📈 Profit Latest', 'callback_data' => 'menu_profit'],
                ],
                [
                    ['text' => '🧾 Submit Bukti Bayar', 'callback_data' => 'menu_pay'],
                ],
            ],
        )->onQueue('notifications');
    }

    private function handleLinkCommand(string $chatId, string $text, TelegramOpsMessage $tpl): void
    {
        $parts = preg_split('/\s+/', trim($text)) ?: [];
        if (count($parts) < 2) {
            $this->tg->sendMessage($chatId, $tpl->botLinkFormatHint());

            return;
        }

        $token = trim((string) $parts[1]);
        if ($token === '') {
            $this->tg->sendMessage($chatId, $tpl->botLinkFormatHint());

            return;
        }

        $hash = hash('sha256', $token);

        $row = DB::table('telegram_pairing_tokens')
            ->where('token_hash', $hash)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first(['id', 'user_id']);

        if ($row === null) {
            $this->tg->sendMessage($chatId, $tpl->botNotLinked());

            return;
        }

        DB::transaction(function () use ($row, $chatId): void {
            DB::table('telegram_pairing_tokens')
                ->where('id', (int) $row->id)
                ->update(['used_at' => now(), 'updated_at' => now()]);

            DB::table('telegram_links')->updateOrInsert(
                ['chat_id' => $chatId],
                [
                    'user_id' => (int) $row->user_id,
                    'linked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        });

        $this->tg->sendMessage($chatId, $tpl->botLinkedOk());
    }

    private function sendUnpaidList(string $chatId): void
    {
        $rows = DB::table('purchase_invoices')
            ->where(function ($q) {
                $q->whereNull('payment_status')->orWhere('payment_status', 'UNPAID');
            })
            ->orderByRaw('COALESCE(due_date, tgl_kirim) asc')
            ->limit(20)
            ->get(['no_faktur', 'supplier_name', 'tgl_kirim', 'due_date', 'grand_total']);

        if ($rows->count() === 0) {
            $this->tg->sendMessage($chatId, "✅ OK\nTidak ada invoice unpaid.");

            return;
        }

        $lines = ['📦 UNPAID SUPPLIER (top 20)', ''];
        foreach ($rows as $r) {
            $lines[] = implode(' | ', [
                (string) $r->no_faktur,
                (string) $r->supplier_name,
                'Kirim: '.(string) $r->tgl_kirim,
                'Due: '.(string) ($r->due_date ?? '-'),
                'Total: Rp '.number_format((int) $r->grand_total, 0, ',', '.'),
            ]);
        }

        $this->tg->sendMessage($chatId, implode("\n", $lines));
    }

    private function sendProfitLatest(string $chatId, TelegramOpsMessage $tpl): void
    {
        $now = CarbonImmutable::instance($this->clock->now());
        $today = $now->setTimezone('Asia/Makassar')->toDateString();

        $last = DB::table('transactions')
            ->where('status', 'COMPLETED')
            ->where('business_date', '<=', $today)
            ->max('business_date');

        $date = $last !== null ? (string) $last : $today;

        $res = $this->profit->aggregate($date, $date, 'daily');
        $row = $res->rows[0] ?? null;

        $this->tg->sendMessage($chatId, $tpl->profitDaily($date, $row));
    }

    private function handleInvoiceNoInput(string $chatId, string $noFaktur, TelegramOpsMessage $tpl): void
    {
        $noFaktur = trim($noFaktur);

        $inv = DB::table('purchase_invoices')
            ->where('no_faktur', $noFaktur)
            ->first(['id', 'no_faktur']);

        if ($inv === null) {
            $this->tg->sendMessage($chatId, $tpl->botInvoiceNotFound($noFaktur));

            return;
        }

        $data = ['purchase_invoice_id' => (int) $inv->id, 'no_faktur' => (string) $inv->no_faktur];
        $this->setConversation($chatId, 'AWAIT_PROOF_UPLOAD', $data);

        $this->tg->sendMessage($chatId, $tpl->botAskUploadProof($noFaktur));
    }

    private function dispatchProofUploadJob(string $chatId, array $msg, array $data, TelegramOpsMessage $tpl): void
    {
        $invoiceId = (int) ($data['purchase_invoice_id'] ?? 0);
        $noFaktur = (string) ($data['no_faktur'] ?? '');

        if ($invoiceId <= 0 || $noFaktur === '') {
            $this->clearConversation($chatId);
            $this->tg->sendMessage($chatId, $tpl->botAskInvoiceNo());

            return;
        }

        $fileId = null;
        $originalName = null;

        if (isset($msg['document']['file_id'])) {
            $fileId = (string) $msg['document']['file_id'];
            $originalName = isset($msg['document']['file_name']) ? (string) $msg['document']['file_name'] : null;
        } elseif (isset($msg['photo']) && is_array($msg['photo']) && count($msg['photo']) > 0) {
            $last = $msg['photo'][count($msg['photo']) - 1];
            if (isset($last['file_id'])) {
                $fileId = (string) $last['file_id'];
            }
            $originalName = 'photo.jpg';
        }

        if ($fileId === null || $fileId === '') {
            $this->tg->sendMessage($chatId, $tpl->botAskUploadProof($noFaktur));

            return;
        }

        $userId = $this->linkedUserId($chatId);
        if ($userId === null) {
            $this->tg->sendMessage($chatId, $tpl->botNotLinked());

            return;
        }

        $messageId = isset($msg['message_id']) ? (string) $msg['message_id'] : null;

        DownloadTelegramPaymentProofJob::dispatch(
            chatId: $chatId,
            invoiceId: $invoiceId,
            noFaktur: $noFaktur,
            submittedByUserId: $userId,
            telegramFileId: $fileId,
            telegramMessageId: $messageId,
            originalFilename: $originalName,
        )->onQueue('notifications');

        // immediate ack (fast)
        $this->tg->sendMessage($chatId, '⏳ Upload diterima. Sedang diproses...');
    }

    private function isLinkedAdminChat(string $chatId): bool
    {
        return $this->linkedUserId($chatId) !== null;
    }

    private function linkedUserId(string $chatId): ?int
    {
        $row = DB::table('telegram_links')->where('chat_id', $chatId)->first(['user_id']);
        if ($row === null) {
            return null;
        }

        return (int) $row->user_id;
    }

    private function getConversation(string $chatId): ?array
    {
        $row = DB::table('telegram_conversations')->where('chat_id', $chatId)->first(['state', 'data_json']);
        if ($row === null) {
            return null;
        }

        $data = [];
        if ($row->data_json !== null && (string) $row->data_json !== '') {
            $decoded = json_decode((string) $row->data_json, true);
            if (is_array($decoded)) {
                $data = $decoded;
            }
        }

        return ['state' => (string) $row->state, 'data' => $data];
    }

    private function setConversation(string $chatId, string $state, ?array $data): void
    {
        DB::table('telegram_conversations')->updateOrInsert(
            ['chat_id' => $chatId],
            [
                'state' => $state,
                'data_json' => $data !== null ? json_encode($data, JSON_THROW_ON_ERROR) : null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        );
    }

    private function clearConversation(string $chatId): void
    {
        DB::table('telegram_conversations')->where('chat_id', $chatId)->delete();
    }
}
