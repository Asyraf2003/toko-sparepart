<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Buat Payroll</title>
</head>
<body>
<div style="max-width:1000px;margin:20px auto;">
    <h1>Buat Payroll Period (Senin–Sabtu)</h1>

    <p><a href="/admin/payroll">Kembali</a></p>

    @if ($errors->any())
        <div>
            <p>Validasi error:</p>
            <ul>
                @foreach ($errors->all() as $e)
                    <li>{{ $e }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="post" action="/admin/payroll">
        @csrf

        <fieldset>
            <legend>Periode</legend>

            <p>
                <label>Week Start (harus Senin)<br>
                    <input type="date" name="week_start" value="{{ old('week_start') }}">
                </label>
            </p>

            <p>
                <label>Week End (harus Sabtu)<br>
                    <input type="date" name="week_end" value="{{ old('week_end') }}">
                </label>
            </p>

            <p>
                <label>Note (opsional)<br>
                    <input type="text" name="note" value="{{ old('note') }}">
                </label>
            </p>
        </fieldset>

        <fieldset>
            <legend>Lines (isi gross dan/atau potongan)</legend>

            <table border="1" cellspacing="0" cellpadding="6">
                <thead>
                <tr>
                    <th>Employee</th>
                    <th>Outstanding Loan</th>
                    <th>Gross Pay</th>
                    <th>Loan Deduction</th>
                    <th>Note</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($employees as $idx => $e)
                    @php
                        $out = (int) ($outstandingByEmployeeId[$e->id] ?? 0);
                    @endphp
                    <tr>
                        <td>
                            {{ $e->name }}
                            <input type="hidden" name="lines[{{ $idx }}][employee_id]" value="{{ $e->id }}">
                        </td>
                        <td>{{ $out }}</td>
                        <td>
                            <input type="number" name="lines[{{ $idx }}][gross_pay]" min="0"
                                   value="{{ old("lines.$idx.gross_pay") }}">
                        </td>
                        <td>
                            <input type="number" name="lines[{{ $idx }}][loan_deduction]" min="0"
                                   value="{{ old("lines.$idx.loan_deduction") }}">
                        </td>
                        <td>
                            <input type="text" name="lines[{{ $idx }}][note]" value="{{ old("lines.$idx.note") }}">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </fieldset>

        <p>
            <button type="submit">Simpan Payroll</button>
            <a href="/admin/payroll">Batal</a>
        </p>
    </form>
</div>
</body>
</html>