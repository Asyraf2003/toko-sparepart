<?php

use Carbon\CarbonImmutable;

it('computes due_date using calendar clamp addMonthNoOverflow', function () {
    // 2026-01-30 + 1 month (Feb 2026 has 28 days) => 2026-02-28
    $tglKirim = CarbonImmutable::parse('2026-01-30');
    $due = $tglKirim->addMonthNoOverflow()->toDateString();

    expect($due)->toBe('2026-02-28');
});

it('computes notify_at as due_date - 5 days even in february', function () {
    $tglKirim = CarbonImmutable::parse('2026-01-30');
    $due = $tglKirim->addMonthNoOverflow(); // 2026-02-28
    $notifyAt = $due->subDays(5)->toDateString();

    // 2026-02-28 - 5 => 2026-02-23
    expect($notifyAt)->toBe('2026-02-23');
});

it('keeps same day when next month contains that day', function () {
    // 2026-06-15 + 1 month => 2026-07-15
    $tglKirim = CarbonImmutable::parse('2026-06-15');
    $due = $tglKirim->addMonthNoOverflow()->toDateString();
    $notifyAt = CarbonImmutable::parse($due)->subDays(5)->toDateString();

    expect($due)->toBe('2026-07-15');
    expect($notifyAt)->toBe('2026-07-10');
});
