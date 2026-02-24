<?php

use App\Application\Ports\Repositories\ProfitReportQueryPort;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('supports daily granularity and returns day bucket even when no data exists', function () {
    /** @var ProfitReportQueryPort $q */
    $q = app(ProfitReportQueryPort::class);

    $date = '2026-02-24';

    $res = $q->aggregate($date, $date, 'daily');

    expect($res->granularity)->toBe('daily');
    expect($res->fromDate)->toBe($date);
    expect($res->toDate)->toBe($date);

    // Implementation builds buckets per day in range, so should yield exactly 1 row.
    expect($res->rows)->toHaveCount(1);
    expect($res->rows[0]->periodKey)->toBe($date);
    expect($res->rows[0]->periodLabel)->toContain('(day)');
});
