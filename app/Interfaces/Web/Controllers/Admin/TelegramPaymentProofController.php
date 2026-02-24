<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\TelegramSenderPort;
use App\Application\Services\TelegramOpsMessage;
use App\Domain\Audit\AuditEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final readonly class TelegramPaymentProofController
{
    public function __construct(
        private AuditLoggerPort $audit,
        private TelegramSenderPort $tg,
    ) {}

    public function index()
    {
        $rows = DB::table('telegram_payment_proof_submissions as s')
            ->join('purchase_invoices as pi', 'pi.id', '=', 's.purchase_invoice_id')
            ->where('s.status', 'PENDING')
            ->orderByDesc('s.id')
            ->limit(200)
            ->get([
                's.id',
                's.created_at',
                's.telegram_chat_id',
                's.original_filename',
                'pi.no_faktur',
                'pi.supplier_name',
                'pi.grand_total',
            ]);

        return response()->view('admin.telegram_payment_proofs.index', ['rows' => $rows]);
    }

    public function show(int $id)
    {
        $row = DB::table('telegram_payment_proof_submissions as s')
            ->join('purchase_invoices as pi', 'pi.id', '=', 's.purchase_invoice_id')
            ->where('s.id', $id)
            ->first([
                's.*',
                'pi.no_faktur',
                'pi.supplier_name',
                'pi.grand_total',
                'pi.payment_status',
            ]);

        abort_if($row === null, 404);

        return response()->view('admin.telegram_payment_proofs.show', ['row' => $row]);
    }

    public function download(int $id): BinaryFileResponse
    {
        $row = DB::table('telegram_payment_proof_submissions')->where('id', $id)->first(['stored_path', 'original_filename']);
        abort_if($row === null, 404);

        $path = (string) $row->stored_path;
        $full = Storage::disk('local')->path($path);

        $name = $row->original_filename !== null ? (string) $row->original_filename : basename($path);

        return response()->download($full, $name);
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            $note = 'approved via admin ui';
        }

        $tpl = TelegramOpsMessage::fromConfig();

        DB::transaction(function () use ($request, $id, $note, $tpl): void {
            $sub = DB::table('telegram_payment_proof_submissions')->where('id', $id)->lockForUpdate()->first();
            if ($sub === null) {
                abort(404);
            }
            if ((string) $sub->status !== 'PENDING') {
                return;
            }

            $pi = DB::table('purchase_invoices')->where('id', (int) $sub->purchase_invoice_id)->lockForUpdate()->first();
            if ($pi === null) {
                abort(404);
            }

            $before = [
                'payment_status' => $pi->payment_status ?? null,
                'paid_at' => $pi->paid_at ?? null,
                'paid_by_user_id' => $pi->paid_by_user_id ?? null,
                'paid_note' => $pi->paid_note ?? null,
            ];

            DB::table('purchase_invoices')->where('id', (int) $pi->id)->update([
                'payment_status' => 'PAID',
                'paid_at' => now(),
                'paid_by_user_id' => $request->user()->id,
                'paid_note' => $note,
                'updated_at' => now(),
            ]);

            DB::table('telegram_payment_proof_submissions')->where('id', $id)->update([
                'status' => 'APPROVED',
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'note' => $note,
                'updated_at' => now(),
            ]);

            $after = [
                'payment_status' => 'PAID',
                'paid_at' => now()->toDateTimeString(),
                'paid_by_user_id' => $request->user()->id,
                'paid_note' => $note,
            ];

            $this->audit->append(new AuditEntry(
                actorId: (int) $request->user()->id,
                actorRole: null,
                entityType: 'PurchaseInvoice',
                entityId: (int) $pi->id,
                action: 'PURCHASE_INVOICE_MARK_PAID',
                reason: $note,
                before: $before,
                after: $after,
                meta: ['source' => 'telegram_proof_approval', 'submission_id' => $id],
            ));

            // notify back to telegram chat (submitter)
            $chatId = (string) $sub->telegram_chat_id;
            $this->tg->sendMessage($chatId, $tpl->botApproved((string) $pi->no_faktur));
        });

        return back();
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $note = trim((string) $request->input('note', ''));
        if ($note === '') {
            $note = 'rejected via admin ui';
        }

        $tpl = TelegramOpsMessage::fromConfig();

        DB::transaction(function () use ($request, $id, $note, $tpl): void {
            $sub = DB::table('telegram_payment_proof_submissions')->where('id', $id)->lockForUpdate()->first();
            if ($sub === null) {
                abort(404);
            }
            if ((string) $sub->status !== 'PENDING') {
                return;
            }

            $pi = DB::table('purchase_invoices')->where('id', (int) $sub->purchase_invoice_id)->first(['id', 'no_faktur']);
            if ($pi === null) {
                abort(404);
            }

            DB::table('telegram_payment_proof_submissions')->where('id', $id)->update([
                'status' => 'REJECTED',
                'reviewed_by_user_id' => $request->user()->id,
                'reviewed_at' => now(),
                'note' => $note,
                'updated_at' => now(),
            ]);

            $this->audit->append(new AuditEntry(
                actorId: (int) $request->user()->id,
                actorRole: null,
                entityType: 'TelegramPaymentProofSubmission',
                entityId: $id,
                action: 'PAYMENT_PROOF_REJECT',
                reason: $note,
                before: ['status' => 'PENDING'],
                after: ['status' => 'REJECTED', 'note' => $note],
                meta: ['purchase_invoice_id' => (int) $sub->purchase_invoice_id],
            ));

            $chatId = (string) $sub->telegram_chat_id;
            $this->tg->sendMessage($chatId, $tpl->botRejected((string) $pi->no_faktur, $note));
        });

        return back();
    }
}