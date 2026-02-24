<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Illuminate\Support\Facades\DB;

final readonly class SetPurchaseInvoicePaymentStatusUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(SetPurchaseInvoicePaymentStatusRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if ($req->purchaseInvoiceId <= 0) {
            throw new \InvalidArgumentException('invalid purchase invoice id');
        }

        $status = strtoupper(trim($req->paymentStatus));
        if (! in_array($status, ['PAID', 'UNPAID'], true)) {
            throw new \InvalidArgumentException('invalid payment status');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $paidNote = $req->paidNote !== null ? trim((string) $req->paidNote) : null;
        if ($paidNote !== null && $paidNote === '') {
            $paidNote = null;
        }
        if ($paidNote !== null && strlen($paidNote) > 255) {
            throw new \InvalidArgumentException('paid_note too long');
        }

        $nowStr = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $status, $paidNote, $reason, $nowStr): void {
            $row = DB::table('purchase_invoices')
                ->where('id', $req->purchaseInvoiceId)
                ->lockForUpdate()
                ->first([
                    'id',
                    'no_faktur',
                    'payment_status',
                    'due_date',
                    'paid_at',
                    'paid_by_user_id',
                    'paid_note',
                ]);

            if ($row === null) {
                throw new \InvalidArgumentException('purchase invoice not found');
            }

            $before = [
                'payment_status' => $row->payment_status !== null ? (string) $row->payment_status : null,
                'due_date' => $row->due_date !== null ? (string) $row->due_date : null,
                'paid_at' => $row->paid_at !== null ? (string) $row->paid_at : null,
                'paid_by_user_id' => $row->paid_by_user_id !== null ? (int) $row->paid_by_user_id : null,
                'paid_note' => $row->paid_note !== null ? (string) $row->paid_note : null,
            ];

            if ($status === 'PAID') {
                $update = [
                    'payment_status' => 'PAID',
                    'paid_at' => $nowStr,
                    'paid_by_user_id' => $req->actorUserId,
                    'paid_note' => $paidNote,
                    'updated_at' => $nowStr,
                ];
                $action = 'PURCHASE_INVOICE_MARK_PAID';
            } else {
                $update = [
                    'payment_status' => 'UNPAID',
                    'paid_at' => null,
                    'paid_by_user_id' => null,
                    'paid_note' => null,
                    'updated_at' => $nowStr,
                ];
                $action = 'PURCHASE_INVOICE_MARK_UNPAID';
            }

            DB::table('purchase_invoices')
                ->where('id', $req->purchaseInvoiceId)
                ->update($update);

            $after = [
                'payment_status' => $update['payment_status'],
                'due_date' => $before['due_date'],
                'paid_at' => $status === 'PAID' ? $nowStr : null,
                'paid_by_user_id' => $status === 'PAID' ? $req->actorUserId : null,
                'paid_note' => $status === 'PAID' ? $paidNote : null,
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: null,
                entityType: 'PurchaseInvoice',
                entityId: $req->purchaseInvoiceId,
                action: $action,
                reason: $reason,
                before: $before,
                after: $after,
                meta: [
                    'policy' => 'payment status only (does not affect profit report)',
                    'no_faktur' => (string) $row->no_faktur,
                ],
            ));
        });
    }
}
