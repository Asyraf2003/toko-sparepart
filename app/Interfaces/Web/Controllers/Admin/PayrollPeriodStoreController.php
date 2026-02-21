<?php

declare(strict_types=1);

namespace App\Interfaces\Web\Controllers\Admin;

use App\Application\UseCases\Payroll\CreatePayrollPeriodLine;
use App\Application\UseCases\Payroll\CreatePayrollPeriodRequest;
use App\Application\UseCases\Payroll\CreatePayrollPeriodUseCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class PayrollPeriodStoreController
{
    public function __invoke(Request $request, CreatePayrollPeriodUseCase $uc): RedirectResponse
    {
        $data = $request->validate([
            'week_start' => ['required', 'date'],
            'week_end' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:255'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'lines.*.gross_pay' => ['nullable', 'integer', 'min:0'],
            'lines.*.loan_deduction' => ['nullable', 'integer', 'min:0'],
            'lines.*.note' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var array<int,array<string,mixed>> $linesInput */
        $linesInput = $data['lines'];

        $lines = [];

        foreach ($linesInput as $i => $row) {
            $gross = $row['gross_pay'] ?? null;
            $ded = $row['loan_deduction'] ?? null;

            if ($gross === null && $ded === null) {
                continue; // skip empty row
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

            // if both zero -> ignore
            if ($grossInt === 0 && $dedInt === 0) {
                continue;
            }

            $lines[] = new CreatePayrollPeriodLine(
                employeeId: (int) $row['employee_id'],
                grossPay: $grossInt,
                loanDeduction: $dedInt,
                note: $row['note'] !== null ? (string) $row['note'] : null,
            );
        }

        if (count($lines) === 0) {
            throw ValidationException::withMessages([
                'lines' => 'Minimal isi 1 payroll line (gross atau potongan).',
            ]);
        }

        $uc->handle(new CreatePayrollPeriodRequest(
            actorUserId: (int) $request->user()->id,
            weekStart: (string) $data['week_start'],
            weekEnd: (string) $data['week_end'],
            note: $data['note'] !== null ? (string) $data['note'] : null,
            lines: $lines,
        ));

        return redirect('/admin/payroll');
    }
}
