<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class ProfitReportSummary
{
    public function __construct(
        public readonly int $revenuePart,
        public readonly int $revenueService,
        public readonly int $roundingAmount,
        public readonly int $revenueTotal,
        public readonly int $cogsTotal,
        public readonly int $expensesTotal,
        public readonly int $payrollGross,
        public readonly int $netProfit,
        public readonly int $missingCogsQty,
    ) {}

    public static function empty(): self
    {
        return new self(
            revenuePart: 0,
            revenueService: 0,
            roundingAmount: 0,
            revenueTotal: 0,
            cogsTotal: 0,
            expensesTotal: 0,
            payrollGross: 0,
            netProfit: 0,
            missingCogsQty: 0,
        );
    }
}

-

<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Application\DTO\Reporting\ProfitReportResult;
use App\Application\DTO\Reporting\ProfitReportRow;
use App\Application\DTO\Reporting\ProfitReportSummary;
use App\Application\Ports\Repositories\ProfitReportQueryPort;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final class EloquentProfitReportQuery implements ProfitReportQueryPort
{
    public function aggregate(string $fromDate, string $toDate, string $granularity): ProfitReportResult
    {
        if (! in_array($granularity, ['weekly', 'monthly'], true)) {
            $granularity = 'weekly';
        }

        // Aggregate per transaction first to avoid join multiplication.
        $partAgg = DB::table('transaction_part_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(line_subtotal) AS part_subtotal')
            ->selectRaw('SUM(COALESCE(unit_cogs_frozen, 0) * qty) AS cogs_total')
            ->selectRaw('SUM(CASE WHEN unit_cogs_frozen IS NULL THEN qty ELSE 0 END) AS missing_cogs_qty')
            ->groupBy('transaction_id');

        $serviceAgg = DB::table('transaction_service_lines')
            ->selectRaw('transaction_id')
            ->selectRaw('SUM(price_manual) AS service_subtotal')
            ->groupBy('transaction_id');

        $txAgg = DB::table('transactions as t')
            ->leftJoinSub($partAgg, 'p', fn ($j) => $j->on('p.transaction_id', '=', 't.id'))
            ->leftJoinSub($serviceAgg, 's', fn ($j) => $j->on('s.transaction_id', '=', 't.id'))
            ->whereBetween('t.business_date', [$fromDate, $toDate])
            ->where('t.status', 'COMPLETED')
            ->select([
                't.business_date as d',
                't.id as transaction_id',
                't.rounding_amount',
                DB::raw('COALESCE(p.part_subtotal, 0) as revenue_part'),
                DB::raw('COALESCE(s.service_subtotal, 0) as revenue_service'),
                DB::raw('COALESCE(p.cogs_total, 0) as cogs_total'),
                DB::raw('COALESCE(p.missing_cogs_qty, 0) as missing_cogs_qty'),
            ]);

        $salesDaily = DB::query()
            ->fromSub($txAgg, 'x')
            ->groupBy('x.d')
            ->orderBy('x.d')
            ->get([
                'x.d',
                DB::raw('SUM(x.rounding_amount) as rounding_amount'),
                DB::raw('SUM(x.revenue_part) as revenue_part'),
                DB::raw('SUM(x.revenue_service) as revenue_service'),
                DB::raw('SUM(x.cogs_total) as cogs_total'),
                DB::raw('SUM(x.missing_cogs_qty) as missing_cogs_qty'),
            ]);

        $expensesDaily = DB::table('expenses')
            ->whereBetween('expense_date', [$fromDate, $toDate])
            ->groupBy('expense_date')
            ->orderBy('expense_date')
            ->get([
                'expense_date as d',
                DB::raw('SUM(amount) as expenses_total'),
            ]);

        // Payroll is weekly (Mon–Sat). We aggregate by payroll_periods range.
        // For monthly bucket: current policy = bucket by month(week_end).
        $payrollWeekly = DB::table('payroll_periods as pp')
            ->join('payroll_lines as pl', 'pl.payroll_period_id', '=', 'pp.id')
            ->whereBetween('pp.week_end', [$fromDate, $toDate])
            ->groupBy('pp.week_start', 'pp.week_end')
            ->orderBy('pp.week_start')
            ->get([
                'pp.week_start',
                'pp.week_end',
                DB::raw('SUM(pl.gross_pay) as payroll_gross'),
            ]);

        $salesByDate = [];
        foreach ($salesDaily as $r) {
            $salesByDate[(string) $r->d] = [
                'rounding' => (int) $r->rounding_amount,
                'part' => (int) $r->revenue_part,
                'service' => (int) $r->revenue_service,
                'cogs' => (int) $r->cogs_total,
                'missing' => (int) $r->missing_cogs_qty,
            ];
        }

        $expensesByDate = [];
        foreach ($expensesDaily as $r) {
            $expensesByDate[(string) $r->d] = (int) $r->expenses_total;
        }

        $buckets = [];
        $from = CarbonImmutable::parse($fromDate);
        $to = CarbonImmutable::parse($toDate);

        for ($d = $from; $d->lte($to); $d = $d->addDay()) {
            $ds = $d->toDateString();

            if ($granularity === 'weekly') {
                $keyDate = $d->startOfWeek(CarbonImmutable::MONDAY);
                $key = $keyDate->toDateString();
                $label = $key.' (week)';
            } else {
                $key = $d->format('Y-m');
                $label = $key.' (month)';
            }

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'label' => $label,
                    'part' => 0,
                    'service' => 0,
                    'rounding' => 0,
                    'cogs' => 0,
                    'missing' => 0,
                    'expenses' => 0,
                    'payroll' => 0,
                ];
            }

            $s = $salesByDate[$ds] ?? null;
            if ($s !== null) {
                $buckets[$key]['part'] += $s['part'];
                $buckets[$key]['service'] += $s['service'];
                $buckets[$key]['rounding'] += $s['rounding'];
                $buckets[$key]['cogs'] += $s['cogs'];
                $buckets[$key]['missing'] += $s['missing'];
            }

            $buckets[$key]['expenses'] += $expensesByDate[$ds] ?? 0;
        }

        foreach ($payrollWeekly as $p) {
            $weekStart = CarbonImmutable::parse((string) $p->week_start);
            $weekEnd = CarbonImmutable::parse((string) $p->week_end);
            $gross = (int) $p->payroll_gross;

            $key = $granularity === 'weekly'
                ? $weekStart->toDateString()
                : $weekEnd->format('Y-m'); // policy as above

            if (! isset($buckets[$key])) {
                $buckets[$key] = [
                    'label' => $granularity === 'weekly' ? ($key.' (week)') : ($key.' (month)'),
                    'part' => 0,
                    'service' => 0,
                    'rounding' => 0,
                    'cogs' => 0,
                    'missing' => 0,
                    'expenses' => 0,
                    'payroll' => 0,
                ];
            }

            $buckets[$key]['payroll'] += $gross;
        }

        ksort($buckets);

        $rows = [];
        $sum = ProfitReportSummary::empty();

        foreach ($buckets as $key => $b) {
            $revenueTotal = $b['part'] + $b['service'] + $b['rounding'];
            $net = $revenueTotal - $b['cogs'] - $b['expenses'] - $b['payroll'];

            $rows[] = new ProfitReportRow(
                periodKey: (string) $key,
                periodLabel: (string) $b['label'],
                revenuePart: (int) $b['part'],
                revenueService: (int) $b['service'],
                roundingAmount: (int) $b['rounding'],
                revenueTotal: (int) $revenueTotal,
                cogsTotal: (int) $b['cogs'],
                expensesTotal: (int) $b['expenses'],
                payrollGross: (int) $b['payroll'],
                netProfit: (int) $net,
                missingCogsQty: (int) $b['missing'],
            );

            $sum = new ProfitReportSummary(
                revenuePart: $sum->revenuePart + (int) $b['part'],
                revenueService: $sum->revenueService + (int) $b['service'],
                roundingAmount: $sum->roundingAmount + (int) $b['rounding'],
                revenueTotal: $sum->revenueTotal + (int) $revenueTotal,
                cogsTotal: $sum->cogsTotal + (int) $b['cogs'],
                expensesTotal: $sum->expensesTotal + (int) $b['expenses'],
                payrollGross: $sum->payrollGross + (int) $b['payroll'],
                netProfit: $sum->netProfit + (int) $net,
                missingCogsQty: $sum->missingCogsQty + (int) $b['missing'],
            );
        }

        return new ProfitReportResult(
            rows: $rows,
            summary: $sum,
            granularity: $granularity,
            fromDate: $fromDate,
            toDate: $toDate,
        );
    }
}

-

<?php

declare(strict_types=1);

namespace App\Application\DTO\Reporting;

final class ProfitReportRow
{
    public function __construct(
        public readonly string $periodKey,   // e.g. 2026-02-16 (week start) OR 2026-02 (month)
        public readonly string $periodLabel, // human label
        public readonly int $revenuePart,
        public readonly int $revenueService,
        public readonly int $roundingAmount,
        public readonly int $revenueTotal,
        public readonly int $cogsTotal,
        public readonly int $expensesTotal,
        public readonly int $payrollGross,
        public readonly int $netProfit,
        public readonly int $missingCogsQty,
    ) {}
}

-

<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifications\Telegram;

use App\Application\DTO\Notifications\LowStockAlertMessage;
use App\Application\Ports\Services\LowStockNotifierPort;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class TelegramLowStockNotifier implements LowStockNotifierPort
{
    public function notifyLowStock(LowStockAlertMessage $msg): void
    {
        $enabled = (bool) config('services.telegram_low_stock.enabled', false);
        if (! $enabled) {
            return;
        }

        $token = (string) config('services.telegram_low_stock.bot_token', '');
        if (trim($token) === '') {
            return;
        }

        $chatIdsRaw = (string) config('services.telegram_low_stock.chat_ids', '');
        $chatIds = $this->parseChatIds($chatIdsRaw);
        if (count($chatIds) === 0) {
            return;
        }

        $text = $this->buildText($msg);
        $url = 'https://api.telegram.org/bot'.$token.'/sendMessage';

        foreach ($chatIds as $chatId) {
            try {
                $resp = Http::timeout(5)->asForm()->post($url, [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);

                if (! $resp->successful()) {
                    Log::warning('telegram_low_stock_send_failed', [
                        'status' => $resp->status(),
                        'product_id' => $msg->productId,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('telegram_low_stock_send_exception', [
                    'product_id' => $msg->productId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function buildText(LowStockAlertMessage $msg): string
    {
        $ts = $msg->occurredAt->format('Y-m-d H:i:s');

        return implode("\n", [
            '⚠️ LOW STOCK',
            $msg->sku.' — '.$msg->name,
            'Available: '.$msg->availableQty,
            'Threshold: '.$msg->threshold,
            'Trigger: '.$msg->triggerType,
            'Time: '.$ts,
        ]);
    }

    /**
     * @return list<string>
     */
    private function parseChatIds(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = array_map('trim', explode(',', $raw));
        $out = [];
        foreach ($parts as $p) {
            if ($p !== '') {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }
}

-

<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

use App\Domain\Audit\AuditEntry;

interface AuditLoggerPort
{
    public function append(AuditEntry $entry): void;
}

-

Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();

            // Supplier (V1: string, no master)
            $table->string('supplier_name', 190)->index();

            // Header fields (from blueprint)
            $table->string('no_faktur', 64)->unique();
            $table->date('tgl_kirim')->index();

            $table->string('kepada', 190)->nullable();
            $table->string('no_pesanan', 64)->nullable()->index();
            $table->string('nama_sales', 190)->nullable();

            // Totals (money in rupiah integer)
            $table->bigInteger('total_bruto')->default(0);
            $table->bigInteger('total_diskon')->default(0);
            $table->bigInteger('total_pajak')->default(0); // header-level tax
            $table->bigInteger('grand_total')->default(0);

            // Traceability
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->index(['tgl_kirim', 'supplier_name']);
        });

-

<?php

use App\Interfaces\Web\Controllers\Auth\LoginController;
use App\Interfaces\Web\Controllers\System\PingController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/__hex/ping', PingController::class);

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth');

Route::get('/', function () {
    $user = request()->user();

    if ($user === null) {
        return redirect('/login');
    }

    return $user->role === User::ROLE_ADMIN
        ? redirect('/admin')
        : redirect('/cashier');
});

require __DIR__.'/admin.php';
require __DIR__.'/cashier.php';

-

<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

-

<?php

declare(strict_types=1);

namespace App\Application\Ports\Services;

use DateTimeImmutable;

interface ClockPort
{
    public function now(): DateTimeImmutable;

    /**
     * Business date in Asia/Makassar (format: YYYY-MM-DD).
     */
    public function todayBusinessDate(): string;
}

-

<?php

declare(strict_types=1);

namespace App\Infrastructure\Clock;

use App\Application\Ports\Services\ClockPort;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

final class SystemClock implements ClockPort
{
    public function now(): DateTimeImmutable
    {
        $tz = (string) config('app.timezone', 'UTC');

        return CarbonImmutable::now($tz);
    }

    public function todayBusinessDate(): string
    {
        return $this->now()->format('Y-m-d');
    }
}
