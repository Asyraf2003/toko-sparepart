<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Catalog\UpdateProductRequest;
use App\Application\UseCases\Catalog\UpdateProductUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ProductUpdateController
{
    public function __invoke(int $productId, Request $request, UpdateProductUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:64', "unique:products,sku,{$productId}"],
            'name' => ['required', 'string', 'max:190'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $uc->handle(new UpdateProductRequest(
            productId: $productId,
            sku: (string) $data['sku'],
            name: (string) $data['name'],
            isActive: (bool) ($data['is_active'] ?? false),
        ));

        return redirect("/admin/products/{$productId}/edit");
    }
}
