<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Payroll\CreateEmployeeLoanRequest;
use App\Application\UseCases\Payroll\CreateEmployeeLoanUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class EmployeeLoanStoreController
{
    public function __invoke(int $employeeId, Request $request, CreateEmployeeLoanUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'loan_date' => ['required', 'date'],
            'amount' => ['required', 'integer', 'min:1'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $uc->handle(new CreateEmployeeLoanRequest(
            actorUserId: (int) $request->user()->id,
            employeeId: $employeeId,
            loanDate: (string) $data['loan_date'],
            amount: (int) $data['amount'],
            note: $data['note'] !== null ? (string) $data['note'] : null,
        ));

        return redirect('/admin/employees');
    }
}
