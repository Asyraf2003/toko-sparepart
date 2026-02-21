<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceLine;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceRequest;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PurchaseInvoiceStoreController
{
    public function __invoke(Request $request, CreatePurchaseInvoiceUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'min:1', 'max:190'],
            'no_faktur' => ['required', 'string', 'min:1', 'max:64', 'unique:purchase_invoices,no_faktur'],
            'tgl_kirim' => ['required', 'date'],
            'kepada' => ['nullable', 'string', 'max:190'],
            'no_pesanan' => ['nullable', 'string', 'max:64'],
            'nama_sales' => ['nullable', 'string', 'max:190'],
            'total_pajak' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.qty' => ['nullable', 'integer', 'min:1'],
            'lines.*.unit_cost' => ['nullable', 'integer', 'min:0'],
            'lines.*.disc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        /** @var array<int,array<string,mixed>> $linesInput */
        $linesInput = $data['lines'];

        $lines = [];
        foreach ($linesInput as $i => $row) {
            $pid = $row['product_id'] ?? null;
            $qty = $row['qty'] ?? null;
            $unitCost = $row['unit_cost'] ?? null;
            $discPercent = $row['disc_percent'] ?? null;

            $allEmpty = ($pid === null && $qty === null && $unitCost === null && $discPercent === null);
            if ($allEmpty) {
                continue;
            }

            if ($pid === null) {
                throw ValidationException::withMessages([
                    "lines.$i.product_id" => 'Product wajib dipilih.',
                ]);
            }
            if ($qty === null) {
                throw ValidationException::withMessages([
                    "lines.$i.qty" => 'Qty wajib diisi.',
                ]);
            }
            if ($unitCost === null) {
                throw ValidationException::withMessages([
                    "lines.$i.unit_cost" => 'Unit cost wajib diisi.',
                ]);
            }

            $disc = $discPercent === null ? 0.0 : (float) $discPercent;

            // percent (0..100) -> basis points (0..10000)
            $discBps = (int) round($disc * 100);

            if ($discBps < 0 || $discBps > 10000) {
                throw ValidationException::withMessages([
                    "lines.$i.disc_percent" => 'Diskon harus di antara 0 sampai 100 (%).',
                ]);
            }

            $lines[] = new CreatePurchaseInvoiceLine(
                productId: (int) $pid,
                qty: (int) $qty,
                unitCost: (int) $unitCost,
                discBps: $discBps,
            );
        }

        if (count($lines) === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal isi 1 line pembelian.',
            ]);
        }

        $uc->handle(new CreatePurchaseInvoiceRequest(
            actorUserId: (int) $request->user()->id,
            supplierName: (string) $data['supplier_name'],
            noFaktur: (string) $data['no_faktur'],
            tglKirim: (string) $data['tgl_kirim'],
            kepada: $data['kepada'] !== null ? (string) $data['kepada'] : null,
            noPesanan: $data['no_pesanan'] !== null ? (string) $data['no_pesanan'] : null,
            namaSales: $data['nama_sales'] !== null ? (string) $data['nama_sales'] : null,
            totalPajak: (int) $data['total_pajak'],
            note: $data['note'] !== null ? (string) $data['note'] : null,
            lines: $lines,
        ));

        return redirect('/admin/purchases');
    }
}
