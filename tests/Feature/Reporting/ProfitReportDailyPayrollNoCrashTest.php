<?php

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('profit report daily does not crash when payroll periods overlap date range', function () {
    $admin = User::query()->create([
        'name' => 'Admin',
        'email' => 'admin-profit@local.test',
        'role' => User::ROLE_ADMIN,
        'password' => Hash::make('12345678'),
    ]);

    $from = '2026-02-24';
    $to = '2026-03-02';

    $tableColumns = function (string $table): array {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            /** @var list<object> $rows */
            $rows = DB::select('SHOW COLUMNS FROM '.$table);
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'name' => (string) $r->Field,
                    'type' => (string) $r->Type,
                    'nullable' => ((string) $r->Null) === 'YES',
                    'default' => $r->Default,
                    'extra' => isset($r->Extra) ? (string) $r->Extra : '',
                ];
            }

            return $out;
        }

        if ($driver === 'sqlite') {
            /** @var list<object> $rows */
            $rows = DB::select('PRAGMA table_info('.$table.')');
            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'name' => (string) $r->name,
                    'type' => (string) $r->type,
                    'nullable' => ((int) $r->notnull) === 0,
                    'default' => $r->dflt_value,
                    'extra' => ((int) $r->pk) === 1 ? 'primary_key' : '',
                ];
            }

            return $out;
        }

        $cols = DB::getSchemaBuilder()->getColumnListing($table);
        $out = [];
        foreach ($cols as $c) {
            $out[] = ['name' => (string) $c, 'type' => '', 'nullable' => true, 'default' => null, 'extra' => ''];
        }

        return $out;
    };

    $enumFirst = function (string $mysqlType): ?string {
        if (! str_starts_with($mysqlType, 'enum(')) {
            return null;
        }

        $inside = trim(substr($mysqlType, 5), ' )');
        if ($inside === '') {
            return null;
        }

        $parts = array_map('trim', explode(',', $inside));
        if (count($parts) === 0) {
            return null;
        }

        $first = trim((string) $parts[0], " '\"");

        return $first !== '' ? $first : null;
    };

    $buildInsert = function (array $cols, array $overrides, int $actorUserId, string $fromDate, string $toDate) use ($enumFirst): array {
        $now = now();
        $data = [];

        foreach ($cols as $c) {
            $name = $c['name'];

            if ($name === 'id') {
                continue;
            }
            if (str_contains((string) $c['extra'], 'auto_increment')) {
                continue;
            }
            if ($c['extra'] === 'primary_key') {
                continue;
            }

            if (array_key_exists($name, $overrides)) {
                $data[$name] = $overrides[$name];

                continue;
            }

            if ($c['default'] !== null && $c['default'] !== 'NULL') {
                continue;
            }

            if ($c['nullable'] === true) {
                continue;
            }

            $t = strtolower((string) $c['type']);

            if (str_ends_with($name, '_at')) {
                $data[$name] = $now;

                continue;
            }

            if ($name === 'week_start' || str_contains($name, 'week_start')) {
                $data[$name] = $fromDate;

                continue;
            }
            if ($name === 'week_end' || str_contains($name, 'week_end')) {
                $data[$name] = $toDate;

                continue;
            }

            if (str_contains($name, 'date')) {
                $data[$name] = $fromDate;

                continue;
            }

            if (str_ends_with($name, '_user_id') || $name === 'user_id' || $name === 'actor_user_id') {
                $data[$name] = $actorUserId;

                continue;
            }

            $ef = $enumFirst((string) $c['type']);
            if ($ef !== null) {
                $data[$name] = $ef;

                continue;
            }

            if (str_contains($t, 'int')) {
                $data[$name] = 1;

                continue;
            }

            if (str_contains($t, 'bool') || str_contains($t, 'tinyint(1)')) {
                $data[$name] = 1;

                continue;
            }

            if (str_contains($t, 'char') || str_contains($t, 'text')) {
                $data[$name] = 'x';

                continue;
            }

            $data[$name] = 'x';
        }

        $colNames = array_column($cols, 'name');
        if (in_array('created_at', $colNames, true) && ! array_key_exists('created_at', $data)) {
            $data['created_at'] = $now;
        }
        if (in_array('updated_at', $colNames, true) && ! array_key_exists('updated_at', $data)) {
            $data['updated_at'] = $now;
        }

        return $data;
    };

    // ✅ Create an employee (FK payroll_lines.employee_id)
    $empCols = $tableColumns('employees');
    $empData = $buildInsert(
        $empCols,
        ['name' => 'Emp Test'],
        (int) $admin->id,
        $from,
        $to,
    );
    $employeeId = (int) DB::table('employees')->insertGetId($empData);

    // ✅ Create payroll period
    $ppCols = $tableColumns('payroll_periods');
    $ppData = $buildInsert(
        $ppCols,
        ['week_start' => $from, 'week_end' => $to],
        (int) $admin->id,
        $from,
        $to,
    );
    $payrollPeriodId = (int) DB::table('payroll_periods')->insertGetId($ppData);

    // ✅ Create payroll line
    $plCols = $tableColumns('payroll_lines');
    $plData = $buildInsert(
        $plCols,
        [
            'payroll_period_id' => $payrollPeriodId,
            'employee_id' => $employeeId,
            'gross_pay' => 700_000,
            'net_paid' => 700_000,
        ],
        (int) $admin->id,
        $from,
        $to,
    );
    DB::table('payroll_lines')->insert($plData);

    /** @var ProfitReportQueryPort $q */
    $q = app(ProfitReportQueryPort::class);

    // Regression check: must not throw TypeError (intdiv with float)
    $res = $q->aggregate('2026-02-24', '2026-02-24', 'daily');

    expect($res->granularity)->toBe('daily');
});
