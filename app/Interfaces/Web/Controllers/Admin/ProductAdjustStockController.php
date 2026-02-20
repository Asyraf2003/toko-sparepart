<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Inventory\AdjustStockRequest;
use App\Application\UseCases\Inventory\AdjustStockUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductAdjustStockController
{
    public function __invoke(int $productId, Request $request, AdjustStockUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'qty_delta' => ['required', 'integer', 'not_in:0'],
            'note' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new AdjustStockRequest(
            productId: $productId,
            qtyDelta: (int) $data['qty_delta'],
            actorUserId: (int) $request->user()->id,
            note: (string) $data['note'],
            refType: 'admin_ui',
            refId: null,
        ));

        return redirect("/admin/products/{$productId}/edit");
    }
}
