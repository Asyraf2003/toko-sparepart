<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Purchasing\SetPurchaseInvoicePaymentStatusRequest;
use App\Application\UseCases\Purchasing\SetPurchaseInvoicePaymentStatusUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PurchaseInvoiceMarkPaidController
{
    public function __invoke(Request $request, int $purchaseInvoiceId, SetPurchaseInvoicePaymentStatusUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'paid_note' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new SetPurchaseInvoicePaymentStatusRequest(
            actorUserId: (int) $request->user()->id,
            purchaseInvoiceId: $purchaseInvoiceId,
            paymentStatus: 'PAID',
            paidNote: $data['paid_note'] !== null ? (string) $data['paid_note'] : null,
            reason: (string) $data['reason'],
        ));

        return redirect('/admin/purchases/'.$purchaseInvoiceId)
            ->with('success', 'Pembelian ditandai PAID.');
    }
}