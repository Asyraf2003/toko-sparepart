<?php

declare(strict_types=1);

namespace App\Application\UseCases\Purchasing;

use App\Application\Ports\Services\AuditLoggerPort;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\TransactionManagerPort;
use App\Domain\Audit\AuditEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class UpdatePurchaseInvoiceHeaderUseCase
{
    public function __construct(
        private TransactionManagerPort $tx,
        private ClockPort $clock,
        private AuditLoggerPort $audit,
    ) {}

    public function handle(UpdatePurchaseInvoiceHeaderRequest $req): void
    {
        if ($req->actorUserId <= 0) {
            throw new \InvalidArgumentException('invalid actor user id');
        }
        if ($req->purchaseInvoiceId <= 0) {
            throw new \InvalidArgumentException('invalid purchase invoice id');
        }

        $supplier = trim($req->supplierName);
        if ($supplier === '') {
            throw new \InvalidArgumentException('supplier name required');
        }

        $noFaktur = trim($req->noFaktur);
        if ($noFaktur === '') {
            throw new \InvalidArgumentException('no_faktur required');
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $req->tglKirim);
        if ($dt === false || $dt->format('Y-m-d') !== $req->tglKirim) {
            throw new \InvalidArgumentException('invalid tgl_kirim format (expected Y-m-d)');
        }

        $reason = trim($req->reason);
        if ($reason === '') {
            throw new \InvalidArgumentException('reason is required');
        }

        $dueDate = CarbonImmutable::parse($req->tglKirim)->addMonthNoOverflow()->toDateString();
        $nowStr = $this->clock->now()->format('Y-m-d H:i:s');

        $this->tx->run(function () use ($req, $supplier, $noFaktur, $reason, $dueDate, $nowStr): void {
            $row = DB::table('purchase_invoices')
                ->where('id', $req->purchaseInvoiceId)
                ->lockForUpdate()
                ->first([
                    'id',
                    'supplier_name',
                    'no_faktur',
                    'tgl_kirim',
                    'due_date',
                    'kepada',
                    'no_pesanan',
                    'nama_sales',
                    'note',
                ]);

            if ($row === null) {
                throw new \InvalidArgumentException('purchase invoice not found');
            }

            $existsNoFaktur = DB::table('purchase_invoices')
                ->where('no_faktur', $noFaktur)
                ->where('id', '!=', $req->purchaseInvoiceId)
                ->exists();

            if ($existsNoFaktur) {
                throw new \InvalidArgumentException('no_faktur already exists');
            }

            $before = [
                'supplier_name' => (string) $row->supplier_name,
                'no_faktur' => (string) $row->no_faktur,
                'tgl_kirim' => (string) $row->tgl_kirim,
                'due_date' => $row->due_date !== null ? (string) $row->due_date : null,
                'kepada' => $row->kepada !== null ? (string) $row->kepada : null,
                'no_pesanan' => $row->no_pesanan !== null ? (string) $row->no_pesanan : null,
                'nama_sales' => $row->nama_sales !== null ? (string) $row->nama_sales : null,
                'note' => $row->note !== null ? (string) $row->note : null,
            ];

            $update = [
                'supplier_name' => $supplier,
                'no_faktur' => $noFaktur,
                'tgl_kirim' => $req->tglKirim,
                'due_date' => $dueDate,
                'kepada' => $req->kepada !== null ? trim((string) $req->kepada) : null,
                'no_pesanan' => $req->noPesanan !== null ? trim((string) $req->noPesanan) : null,
                'nama_sales' => $req->namaSales !== null ? trim((string) $req->namaSales) : null,
                'note' => $req->note !== null ? trim((string) $req->note) : null,
                'updated_at' => $nowStr,
            ];

            DB::table('purchase_invoices')
                ->where('id', $req->purchaseInvoiceId)
                ->update($update);

            $after = [
                'supplier_name' => $update['supplier_name'],
                'no_faktur' => $update['no_faktur'],
                'tgl_kirim' => $update['tgl_kirim'],
                'due_date' => $update['due_date'],
                'kepada' => $update['kepada'],
                'no_pesanan' => $update['no_pesanan'],
                'nama_sales' => $update['nama_sales'],
                'note' => $update['note'],
            ];

            $this->audit->append(new AuditEntry(
                actorId: $req->actorUserId,
                actorRole: null,
                entityType: 'PurchaseInvoice',
                entityId: $req->purchaseInvoiceId,
                action: 'PURCHASE_INVOICE_UPDATE',
                reason: $reason,
                before: $before,
                after: $after,
                meta: [
                    'policy' => 'header-only (no line edit)',
                    'due_date_policy' => 'addMonthNoOverflow(tgl_kirim)',
                ],
            ));
        });
    }
}
