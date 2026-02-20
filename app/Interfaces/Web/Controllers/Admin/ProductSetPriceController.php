<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Catalog\SetSellingPriceRequest;
use App\Application\UseCases\Catalog\SetSellingPriceUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductSetPriceController
{
    public function __invoke(int $productId, Request $request, SetSellingPriceUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'sell_price_current' => ['required', 'integer', 'min:0'],
            'note' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new SetSellingPriceRequest(
            productId: $productId,
            sellPriceCurrent: (int) $data['sell_price_current'],
            actorUserId: (int) $request->user()->id,
            note: (string) $data['note'],
        ));

        return redirect("/admin/products/{$productId}/edit");
    }
}
