<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\ProductStockQueryPort;
use Illuminate\View\View;

final class PurchaseInvoiceCreateController
{
    public function __invoke(ProductStockQueryPort $query): View
    {
        $products = $query->list(
            search: null,
            onlyActive: true,
        );

        return view('admin.purchases.create', [
            'products' => $products,
        ]);
    }
}
