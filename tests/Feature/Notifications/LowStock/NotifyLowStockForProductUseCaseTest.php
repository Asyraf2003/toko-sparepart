<?php

use App\Application\DTO\Notifications\LowStockAlertMessage;
use App\Application\Ports\Services\ClockPort;
use App\Application\Ports\Services\LowStockNotifierPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use Database\Seeders\DevEnsureInventoryStocksSeeder;
use Database\Seeders\DevSampleProductsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

final class FakeLowStockNotifier implements LowStockNotifierPort
{
    /** @var list<LowStockAlertMessage> */
    public array $msgs = [];

    public bool $throw = false;

    public function notifyLowStock(LowStockAlertMessage $msg): void
    {
        if ($this->throw) {
            throw new RuntimeException('telegram send failed');
        }
        $this->msgs[] = $msg;
    }
}

function seedOneProductAndStock(): array
{
    // avoid assumptions about required columns by using your existing seeders
    test()->seed(DevSampleProductsSeeder::class);
    test()->seed(DevEnsureInventoryStocksSeeder::class);

    $product = DB::table('products')->orderBy('id')->first(['id']);
    expect($product)->not->toBeNull();

    $pid = (int) $product->id;

    // Ensure stock row exists
    $stock = DB::table('inventory_stocks')->where('product_id', $pid)->first(['product_id']);
    expect($stock)->not->toBeNull();

    return [$pid];
}

function setClockNow(string $ts): void
{
    test()->mock(ClockPort::class, function (MockInterface $m) use ($ts): void {
        $m->shouldReceive('now')->andReturn(new DateTimeImmutable($ts));
    });
}

it('does nothing when product is inactive', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 0,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 5,
        'reserved_qty' => 0,
    ]);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.reset_on_recover', true);
    config()->set('services.telegram_low_stock.min_interval_seconds', 3600);
    config()->set('services.telegram_low_stock.throttle_on_failure', true);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(0);
    expect(DB::table('low_stock_notification_states')->where('product_id', $pid)->count())->toBe(0);
});

it('resets state on recover when available > threshold and reset_on_recover=true', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    // existing state
    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => '2026-02-24 09:00:00',
        'last_notified_available_qty' => 5,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // recovered stock
    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 50,
        'reserved_qty' => 0,
    ]);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.reset_on_recover', true);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(0);
    expect(DB::table('low_stock_notification_states')->where('product_id', $pid)->count())->toBe(0);
});

it('notifies and updates state when available <= threshold and state is empty', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 5,
        'reserved_qty' => 0,
    ]);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.min_interval_seconds', 3600);
    config()->set('services.telegram_low_stock.throttle_on_failure', true);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(1);

    $state = DB::table('low_stock_notification_states')
        ->where('product_id', $pid)
        ->first(['last_notified_at', 'last_notified_available_qty']);

    expect($state)->not->toBeNull();
    expect((string) $state->last_notified_available_qty)->toBe('5');
    expect((string) $state->last_notified_at)->toContain('2026-02-24');
});

it('does not notify if min interval not passed and not more critical', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 7,
        'reserved_qty' => 0,
    ]);

    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => '2026-02-24 09:50:00',
        'last_notified_available_qty' => 7, // same => not more critical
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.min_interval_seconds', 3600);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(0);
});

it('notifies immediately when more critical even if min interval not passed', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 3,
        'reserved_qty' => 0,
    ]);

    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => '2026-02-24 09:59:55', // interval not passed
        'last_notified_available_qty' => 7,          // was higher => now more critical
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.min_interval_seconds', 3600);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(1);
    expect($fake->msgs[0]->availableQty)->toBe(3);
});

it('throttle_on_failure controls whether state updates when notifier throws', function () {
    [$pid] = seedOneProductAndStock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 5,
        'reserved_qty' => 0,
    ]);

    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => '2026-02-24 09:00:00',
        'last_notified_available_qty' => 6,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fake = new FakeLowStockNotifier;
    $fake->throw = true;
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.min_interval_seconds', 0);

    // Case 1: throttle_on_failure=false => state should NOT be updated
    config()->set('services.telegram_low_stock.throttle_on_failure', false);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    $state1 = DB::table('low_stock_notification_states')
        ->where('product_id', $pid)
        ->first(['last_notified_at', 'last_notified_available_qty']);

    expect((string) $state1->last_notified_at)->toBe('2026-02-24 09:00:00');
    expect((int) $state1->last_notified_available_qty)->toBe(6);

    // Case 2: throttle_on_failure=true => state SHOULD be updated
    config()->set('services.telegram_low_stock.throttle_on_failure', true);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    $state2 = DB::table('low_stock_notification_states')
        ->where('product_id', $pid)
        ->first(['last_notified_at', 'last_notified_available_qty']);

    expect((string) $state2->last_notified_at)->toContain('2026-02-24');
    expect((int) $state2->last_notified_available_qty)->toBe(5);
});
