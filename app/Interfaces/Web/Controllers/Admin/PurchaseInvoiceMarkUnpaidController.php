<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Purchasing\SetPurchaseInvoicePaymentStatusRequest;
use App\Application\UseCases\Purchasing\SetPurchaseInvoicePaymentStatusUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseInvoiceMarkUnpaidController
{
    public function __invoke(Request $request, int $purchaseInvoiceId, SetPurchaseInvoicePaymentStatusUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new SetPurchaseInvoicePaymentStatusRequest(
            actorUserId: (int) $request->user()->id,
            purchaseInvoiceId: $purchaseInvoiceId,
            paymentStatus: 'UNPAID',
            paidNote: null,
            reason: (string) $data['reason'],
        ));

        return redirect('/admin/purchases/'.$purchaseInvoiceId)
            ->with('success', 'Pembelian ditandai UNPAID.');
    }
}
