<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Expenses\CreateExpenseRequest;
use App\Application\UseCases\Expenses\CreateExpenseUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ExpenseStoreController
{
    public function __invoke(Request $request, CreateExpenseUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'string', 'min:1', 'max:64'],
            'amount' => ['required', 'integer', 'min:0'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $uc->handle(new CreateExpenseRequest(
            actorUserId: (int) $request->user()->id,
            expenseDate: (string) $data['expense_date'],
            category: (string) $data['category'],
            amount: (int) $data['amount'],
            note: $data['note'] !== null ? (string) $data['note'] : null,
        ));

        return redirect('/admin/expenses');
    }
}
