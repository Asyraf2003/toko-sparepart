<?php

declare(strict_types=1);

use App\Application\DTO\Notifications\LowStockAlertMessage;
use App\Application\Ports\Services\LowStockNotifierPort;
use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

final class FakeLowStockNotifier implements LowStockNotifierPort
{
    /** @var list<LowStockAlertMessage> */
    public array $messages = [];

    public function notifyLowStock(LowStockAlertMessage $msg): void
    {
        $this->messages[] = $msg;
    }
}

it('sends alert when available <= threshold, and throttles within interval', function () {
    config()->set('services.telegram_low_stock.reset_on_recover', true);
    config()->set('services.telegram_low_stock.throttle_on_failure', true);
    config()->set('services.telegram_low_stock.min_interval_seconds', 86400);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    $nowStr = now()->format('Y-m-d H:i:s');

    $pid = (int) DB::table('products')->insertGetId([
        'sku' => 'SKU-1',
        'name' => 'Product A',
        'sell_price_current' => 10000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 0,
        'created_at' => $nowStr,
        'updated_at' => $nowStr,
    ]);

    DB::table('inventory_stocks')->insert([
        'product_id' => $pid,
        'on_hand_qty' => 2,
        'reserved_qty' => 0,
        'created_at' => $nowStr,
        'updated_at' => $nowStr,
    ]);

    $uc = app(NotifyLowStockForProductUseCase::class);

    $uc->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'ADJUSTMENT',
        actorUserId: 1,
    ));

    expect(count($fake->messages))->toBe(1);

    // Call again immediately: should be throttled (same available, within 24h)
    $uc->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'ADJUSTMENT',
        actorUserId: 1,
    ));

    expect(count($fake->messages))->toBe(1);
});

it('allows alert when stock gets more critical even within interval', function () {
    config()->set('services.telegram_low_stock.reset_on_recover', true);
    config()->set('services.telegram_low_stock.throttle_on_failure', true);
    config()->set('services.telegram_low_stock.min_interval_seconds', 86400);

    $fake = new FakeLowStockNotifier;
    app()->instance(LowStockNotifierPort::class, $fake);

    $nowStr = now()->format('Y-m-d H:i:s');

    $pid = (int) DB::table('products')->insertGetId([
        'sku' => 'SKU-2',
        'name' => 'Product B',
        'sell_price_current' => 10000,
        'min_stock_threshold' => 3,
        'is_active' => true,
        'avg_cost' => 0,
        'created_at' => $nowStr,
        'updated_at' => $nowStr,
    ]);

    DB::table('inventory_stocks')->insert([
        'product_id' => $pid,
        'on_hand_qty' => 2,
        'reserved_qty' => 0,
        'created_at' => $nowStr,
        'updated_at' => $nowStr,
    ]);

    // seed throttle state: last notified at now, last avail = 2
    DB::table('low_stock_notification_states')->insert([
        'product_id' => $pid,
        'last_notified_at' => $nowStr,
        'last_notified_available_qty' => 2,
        'created_at' => $nowStr,
        'updated_at' => $nowStr,
    ]);

    // Make it more critical: available becomes 1
    DB::table('inventory_stocks')->where('product_id', $pid)->update([
        'on_hand_qty' => 1,
        'updated_at' => $nowStr,
    ]);

    $uc = app(NotifyLowStockForProductUseCase::class);

    $uc->handle(new NotifyLowStockForProductRequest(
        productId: $pid,
        triggerType: 'SALE_OUT',
        actorUserId: 1,
    ));

    expect(count($fake->messages))->toBe(1);
});
