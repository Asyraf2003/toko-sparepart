<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Catalog\CreateProductRequest;
use App\Application\UseCases\Catalog\CreateProductUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductStoreController
{
    public function __invoke(Request $request, CreateProductUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:64', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:190'],
            'sell_price_current' => ['required', 'integer', 'min:0'],
            'min_stock_threshold' => ['required', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $uc->handle(new CreateProductRequest(
            sku: (string) $data['sku'],
            name: (string) $data['name'],
            sellPriceCurrent: (int) $data['sell_price_current'],
            minStockThreshold: (int) $data['min_stock_threshold'],
            isActive: (bool) ($data['is_active'] ?? true),
        ));

        return redirect('/admin/products');
    }
}
