<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Purchasing\UpdatePurchaseInvoiceHeaderRequest;
use App\Application\UseCases\Purchasing\UpdatePurchaseInvoiceHeaderUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseInvoiceUpdateController
{
    public function __invoke(Request $request, int $purchaseInvoiceId, UpdatePurchaseInvoiceHeaderUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'supplier_name' => ['required', 'string', 'min:1', 'max:190'],
            'no_faktur' => ['required', 'string', 'min:1', 'max:64'],
            'tgl_kirim' => ['required', 'date'],
            'kepada' => ['nullable', 'string', 'max:190'],
            'no_pesanan' => ['nullable', 'string', 'max:64'],
            'nama_sales' => ['nullable', 'string', 'max:190'],
            'note' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new UpdatePurchaseInvoiceHeaderRequest(
            actorUserId: (int) $request->user()->id,
            purchaseInvoiceId: $purchaseInvoiceId,
            supplierName: (string) $data['supplier_name'],
            noFaktur: (string) $data['no_faktur'],
            tglKirim: (string) $data['tgl_kirim'],
            kepada: $data['kepada'] !== null ? (string) $data['kepada'] : null,
            noPesanan: $data['no_pesanan'] !== null ? (string) $data['no_pesanan'] : null,
            namaSales: $data['nama_sales'] !== null ? (string) $data['nama_sales'] : null,
            note: $data['note'] !== null ? (string) $data['note'] : null,
            reason: (string) $data['reason'],
        ));

        return redirect('/admin/purchases/'.$purchaseInvoiceId);
    }
}
