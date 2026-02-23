<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Payroll\UpdatePayrollPeriodLine;
use App\Application\UseCases\Payroll\UpdatePayrollPeriodRequest;
use App\Application\UseCases\Payroll\UpdatePayrollPeriodUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PayrollPeriodUpdateController
{
    public function __invoke(Request $request, int $payrollPeriodId, UpdatePayrollPeriodUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'week_end' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'reason' => ['required', 'string', 'min:1', 'max:255'],

            // lines boleh dikirim, tapi nanti usecase yang enforce locked/unlocked
            'lines' => ['nullable', 'array'],
            'lines.*.employee_id' => ['required_with:lines', 'integer', 'exists:employees,id'],
            'lines.*.gross_pay' => ['nullable', 'integer', 'min:0'],
            'lines.*.loan_deduction' => ['nullable', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var array<int,array<string,mixed>> $linesInput */
        $linesInput = $data['lines'] ?? [];

        $lines = [];
        foreach ($linesInput as $i => $row) {
            $gross = $row['gross_pay'] ?? null;
            $ded = $row['loan_deduction'] ?? null;

            if ($gross === null && $ded === null) {
                continue;
            }

            if ($gross === null) {
                throw ValidationException::withMessages([
                    "lines.$i.gross_pay" => 'Gross pay wajib diisi jika ada potongan atau ingin membuat payroll line.',
                ]);
            }

            $grossInt = (int) $gross;
            $dedInt = (int) ($ded ?? 0);

            if ($dedInt > $grossInt) {
                throw ValidationException::withMessages([
                    "lines.$i.loan_deduction" => 'Potongan hutang tidak boleh melebihi gross pay.',
                ]);
            }

            if ($grossInt === 0 && $dedInt === 0) {
                continue;
            }

            $lines[] = new UpdatePayrollPeriodLine(
                employeeId: (int) $row['employee_id'],
                grossPay: $grossInt,
                loanDeduction: $dedInt,
                note: $row['note'] !== null ? (string) $row['note'] : null,
            );
        }

        $uc->handle(new UpdatePayrollPeriodRequest(
            actorUserId: (int) $request->user()->id,
            payrollPeriodId: $payrollPeriodId,
            weekStart: (string) $data['week_start'],
            weekEnd: (string) $data['week_end'],
            note: $data['note'] !== null ? (string) $data['note'] : null,
            reason: (string) $data['reason'],
            lines: $lines,
        ));

        return redirect('/admin/payroll/'.$payrollPeriodId);
    }
}
