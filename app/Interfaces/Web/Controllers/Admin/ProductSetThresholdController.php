<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Catalog\SetMinStockThresholdRequest;
use App\Application\UseCases\Catalog\SetMinStockThresholdUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductSetThresholdController
{
    public function __invoke(int $productId, Request $request, SetMinStockThresholdUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'min_stock_threshold' => ['required', 'integer', 'min:0'],
            'note' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $uc->handle(new SetMinStockThresholdRequest(
            productId: $productId,
            minStockThreshold: (int) $data['min_stock_threshold'],
            actorUserId: (int) $request->user()->id,
            note: (string) $data['note'],
        ));

        return redirect("/admin/products/{$productId}/edit");
    }
}
