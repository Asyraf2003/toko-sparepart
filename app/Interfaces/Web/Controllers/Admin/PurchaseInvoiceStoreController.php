<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceLine;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceRequest;
use App\Application\UseCases\Purchasing\CreatePurchaseInvoiceUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class PurchaseInvoiceStoreController
{
    public function __invoke(Request $request, CreatePurchaseInvoiceUseCase $uc): RedirectResponse
    {
        // 1) Ambil payload mentah
        $payload = $request->all();

        // 2) FILTER: buang baris placeholder yang benar-benar kosong.
        // Karena disc_percent default "0", indikator kosong hanya pakai: product_id / qty / unit_cost
        $rawLines = $payload['lines'] ?? [];
        if (! is_array($rawLines)) {
            $rawLines = [];
        }

        $filteredLines = array_values(array_filter($rawLines, function ($row): bool {
            if (! is_array($row)) {
                return false;
            }

            $pid = trim((string) ($row['product_id'] ?? ''));
            $qty = trim((string) ($row['qty'] ?? ''));
            $cost = trim((string) ($row['unit_cost'] ?? ''));

            // keep jika user mengisi salah satu dari 3 field utama
            return $pid !== '' || $qty !== '' || $cost !== '';
        }));

        $payload['lines'] = $filteredLines;

        // 3) Validasi: setelah difilter, minimal 1 line harus ada
        $v = Validator::make($payload, [
            'supplier_name' => ['required', 'string', 'max:255'],
            'no_faktur' => ['required', 'string', 'max:255', Rule::unique('purchase_invoices', 'no_faktur')],
            'tgl_kirim' => ['required', 'date_format:Y-m-d'],
            'kepada' => ['nullable', 'string', 'max:255'],
            'no_pesanan' => ['nullable', 'string', 'max:255'],
            'nama_sales' => ['nullable', 'string', 'max:255'],
            'total_pajak' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'lines.*.qty' => ['required', 'integer', 'min:1'],
            'lines.*.unit_cost' => ['required', 'integer', 'min:0'],
            'lines.*.disc_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $validated = $v->validate();

        // 4) Map lines -> DTO UseCase (hexagonal: controller -> usecase)
        $actorId = (int) (Auth::id() ?? 0);

        $lines = [];
        foreach ($validated['lines'] as $row) {
            $discPercent = (float) ($row['disc_percent'] ?? 0);
            $discBps = (int) round($discPercent * 100); // 10.00% => 1000 bps

            $lines[] = new CreatePurchaseInvoiceLine(
                productId: (int) $row['product_id'],
                qty: (int) $row['qty'],
                unitCost: (int) $row['unit_cost'],
                discBps: $discBps,
            );
        }

        try {
            $uc->handle(new CreatePurchaseInvoiceRequest(
                actorUserId: $actorId,
                supplierName: (string) $validated['supplier_name'],
                noFaktur: (string) $validated['no_faktur'],
                tglKirim: (string) $validated['tgl_kirim'],
                kepada: $validated['kepada'] ?? null,
                noPesanan: $validated['no_pesanan'] ?? null,
                namaSales: $validated['nama_sales'] ?? null,
                totalPajak: (int) $validated['total_pajak'],
                note: $validated['note'] ?? null,
                lines: $lines,
            ));
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }

        return redirect('/admin/purchases')
            ->with('success', 'Pembelian berhasil disimpan.');
    }
}