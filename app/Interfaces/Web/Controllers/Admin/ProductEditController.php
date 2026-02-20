<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\ProductStockQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductEditController
{
    public function __invoke(int $productId, Request $request, ProductStockQueryPort $query): View
    {
        $row = $query->findByProductId($productId);
        if ($row === null) {
            abort(404);
        }

        return view('admin.products.edit', [
            'row' => $row,
        ]);
    }
}
