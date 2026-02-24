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

if (! function_exists('seedOneProductAndStock__notify_low_stock')) {
    function seedOneProductAndStock__notify_low_stock(): array
    {
        test()->seed(DevSampleProductsSeeder::class);
        test()->seed(DevEnsureInventoryStocksSeeder::class);

        $product = DB::table('products')->orderBy('id')->first(['id']);
        expect($product)->not->toBeNull();

        $pid = (int) $product->id;

        $stock = DB::table('inventory_stocks')->where('product_id', $pid)->first(['product_id']);
        expect($stock)->not->toBeNull();

        return [$pid];
    }
}

if (! function_exists('setClockNow__notify_low_stock')) {
    function setClockNow__notify_low_stock(string $ts): void
    {
        test()->mock(ClockPort::class, function (MockInterface $m) use ($ts): void {
            $m->shouldReceive('now')->andReturn(new DateTimeImmutable($ts));
        });
    }
}

it('does nothing when product is inactive', function () {
    [$pid] = seedOneProductAndStock__notify_low_stock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 0,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 5,
        'reserved_qty' => 0,
    ]);

    $fake = new class implements LowStockNotifierPort {
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
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
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
    [$pid] = seedOneProductAndStock__notify_low_stock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => '2026-02-24 09:00:00',
        'last_notified_available_qty' => 5,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 50,
        'reserved_qty' => 0,
    ]);

    $fake = new class implements LowStockNotifierPort {
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
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
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
    [$pid] = seedOneProductAndStock__notify_low_stock();

    DB::table('products')->where('id', $pid)->update([
        'is_active' => 1,
        'min_stock_threshold' => 10,
    ]);

    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 5,
        'reserved_qty' => 0,
    ]);

    $fake = new class implements LowStockNotifierPort {
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
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
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
    expect((int) $state->last_notified_available_qty)->toBe(5);
    expect((string) $state->last_notified_at)->toContain('2026-02-24');
});

it('does not notify if min interval not passed and not more critical', function () {
    [$pid] = seedOneProductAndStock__notify_low_stock();

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
        'last_notified_available_qty' => 7,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fake = new class implements LowStockNotifierPort {
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
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
    config()->set('services.telegram_low_stock.min_interval_seconds', 3600);

    app(NotifyLowStockForProductUseCase::class)->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'TEST',
        actorUserId: null,
    ));

    expect($fake->msgs)->toHaveCount(0);
});

it('notifies immediately when more critical even if min interval not passed', function () {
    [$pid] = seedOneProductAndStock__notify_low_stock();

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
        'last_notified_at' => '2026-02-24 09:59:55',
        'last_notified_available_qty' => 7,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $fake = new class implements LowStockNotifierPort {
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
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
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
    [$pid] = seedOneProductAndStock__notify_low_stock();

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

    $fake = new class implements LowStockNotifierPort {
        /** @var list<LowStockAlertMessage> */
        public array $msgs = [];
        public bool $throw = true;

        public function notifyLowStock(LowStockAlertMessage $msg): void
        {
            if ($this->throw) {
                throw new RuntimeException('telegram send failed');
            }
            $this->msgs[] = $msg;
        }
    };
    app()->instance(LowStockNotifierPort::class, $fake);

    setClockNow__notify_low_stock('2026-02-24 10:00:00');
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