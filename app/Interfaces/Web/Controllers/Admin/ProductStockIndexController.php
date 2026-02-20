<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\Ports\Repositories\ProductStockQueryPort;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ProductStockIndexController
{
    public function __invoke(Request $request, ProductStockQueryPort $query): View
    {
        $search = $request->string('q')->trim()->value();

        $rows = $query->list(
            search: $search !== '' ? $search : null,
            onlyActive: true,
        );

        return view('admin.products.index', [
            'q' => $search,
            'rows' => $rows,
        ]);
    }
}
