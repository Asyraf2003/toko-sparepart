<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use App\Application\Ports\Services\TelegramSenderPort;
use App\Application\Services\TelegramOpsMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class DownloadTelegramPaymentProofJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $chatId,
        public readonly int $invoiceId,
        public readonly string $noFaktur,
        public readonly int $submittedByUserId,
        public readonly string $telegramFileId,
        public readonly ?string $telegramMessageId,
        public readonly ?string $originalFilename,
    ) {}

    public function handle(TelegramSenderPort $tg): void
    {
        $enabled = (bool) config('services.telegram_ops.enabled', false);
        if (! $enabled) {
            return;
        }

        $token = (string) config('services.telegram_ops.bot_token', '');
        if (trim($token) === '') {
            return;
        }

        // Idempotency by telegram message id (best-effort)
        if ($this->telegramMessageId !== null && trim($this->telegramMessageId) !== '') {
            $exists = DB::table('telegram_payment_proof_submissions')
                ->where('telegram_chat_id', $this->chatId)
                ->where('telegram_message_id', $this->telegramMessageId)
                ->exists();

            if ($exists) {
                return;
            }
        }

        $filePath = TelegramBotApi::getFilePath($token, $this->telegramFileId);
        if ($filePath === '') {
            return;
        }

        $bin = TelegramBotApi::downloadFile($token, $filePath);
        if ($bin === '') {
            return;
        }

        $tpl = TelegramOpsMessage::fromConfig();

        DB::transaction(function () use ($tg, $tpl, $bin): void {
            $submissionId = (int) DB::table('telegram_payment_proof_submissions')->insertGetId([
                'purchase_invoice_id' => $this->invoiceId,
                'submitted_by_user_id' => $this->submittedByUserId,
                'telegram_chat_id' => $this->chatId,

                'telegram_file_id' => $this->telegramFileId,
                'telegram_message_id' => $this->telegramMessageId,

                'stored_path' => 'private/telegram/proofs/pending.bin',
                'original_filename' => $this->originalFilename,

                'status' => 'PENDING',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $safeName = $this->originalFilename !== null && trim($this->originalFilename) !== ''
                ? preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $this->originalFilename)
                : 'proof.bin';

            $storePath = 'private/telegram/proofs/'.$submissionId.'_'.$safeName;

            Storage::disk('local')->put($storePath, $bin);

            DB::table('telegram_payment_proof_submissions')
                ->where('id', $submissionId)
                ->update([
                    'stored_path' => $storePath,
                    'updated_at' => now(),
                ]);

            // clear conversation (best effort)
            DB::table('telegram_conversations')->where('chat_id', $this->chatId)->delete();

            // reply pending
            $tg->sendMessage($this->chatId, $tpl->botProofSubmittedPending());
        });
    }
}
