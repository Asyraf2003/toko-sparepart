<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class ExpenseIndexController
{
    public function __invoke(Request $request): View
    {
        $search = $request->string('q')->trim()->value();

        $qb = DB::table('expenses')->orderByDesc('expense_date')->orderByDesc('id');

        if ($search !== '') {
            $qb->where('category', 'like', "%{$search}%");
        }

        $rows = $qb->limit(200)->get([
            'id',
            'expense_date',
            'category',
            'amount',
            'note',
            'created_at',
        ]);

        return view('admin.expenses.index', [
            'q' => $search,
            'rows' => $rows,
        ]);
    }
}
