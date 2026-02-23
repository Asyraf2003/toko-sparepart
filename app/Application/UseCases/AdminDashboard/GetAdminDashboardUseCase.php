<?php

declare(strict_types=1);

namespace App\Application\UseCases\AdminDashboard;

use App\Application\Ports\Services\ClockPort;
use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class GetAdminDashboardUseCase
{
    public function __construct(
        private ClockPort $clock,
    ) {}

    /**
     * @return array{
     *   kpi: array{
     *     today: array{
     *       business_date: string,
     *       revenue: int,
     *       tx_count: int,
     *       cash_net: int
     *     },
     *     mtd: array{
     *       month_start: string,
     *       purchases_total: int,
     *       expenses_total: int
     *     },
     *     low_stock_count: int
     *   },
     *   charts: array{
     *     revenue_daily: list<array{date:string,value:int}>,
     *     cash_net_daily: list<array{date:string,value:int}>,
     *     payment_split: array{labels:list<string>,series:list<int>},
     *     ohlc_daily: list<array{x:string,y:array{0:int,1:int,2:int,3:int}}>
     *   },
     *   tables: array{
     *     low_stock_items: list<array{sku:string,name:string,threshold:int,on_hand:int,reserved:int,available:int}>,
     *     recent_purchases: list<array{id:int,tgl_kirim:string,no_faktur:string,supplier_name:string,grand_total:int}>,
     *     recent_audits: list<array{id:int,created_at:string,action:string,entity_type:string,entity_id:?int,reason:?string,actor_user_id:?int}>
     *   }
     * }
     */
    public function handle(int $days = 14): array
    {
        if ($days < 7) {
            $days = 7;
        }
        if ($days > 60) {
            $days = 60;
        }

        $todayStr = $this->clock->todayBusinessDate();
        $today = DateTimeImmutable::createFromFormat('Y-m-d', $todayStr);
        if ($today === false || $today->format('Y-m-d') !== $todayStr) {
            throw new \RuntimeException('invalid business date from clock');
        }

        $from = $today->sub(new DateInterval('P'.(string) ($days - 1).'D'));
        $fromStr = $from->format('Y-m-d');

        $monthStart = $today->modify('first day of this month');
        $monthStartStr = $monthStart->format('Y-m-d');

        // --- transactions totals per transaction (for charts + KPI) ---
        $partsSub = DB::table('transaction_part_lines')
            ->selectRaw('transaction_id, SUM(line_subtotal) AS part_total')
            ->groupBy('transaction_id');

        $servicesSub = DB::table('transaction_service_lines')
            ->selectRaw('transaction_id, SUM(price_manual) AS service_total')
            ->groupBy('transaction_id');

        /** @var list<object> $tx */
        $tx = DB::table('transactions as t')
            ->leftJoinSub($partsSub, 'p', 'p.transaction_id', '=', 't.id')
            ->leftJoinSub($servicesSub, 's', 's.transaction_id', '=', 't.id')
            ->whereBetween('t.business_date', [$fromStr, $todayStr])
            ->where('t.status', '=', 'COMPLETED')
            ->where('t.payment_status', '=', 'PAID')
            ->orderBy('t.business_date')
            ->orderBy('t.completed_at')
            ->get([
                't.id',
                't.business_date',
                't.payment_method',
                't.completed_at',
                't.rounding_amount',
                't.cash_received',
                't.cash_change',
                DB::raw('COALESCE(p.part_total,0) AS part_total'),
                DB::raw('COALESCE(s.service_total,0) AS service_total'),
                DB::raw('(COALESCE(p.part_total,0) + COALESCE(s.service_total,0) + t.rounding_amount) AS grand_total'),
            ])->all();

        // Prepare date buckets
        $dates = [];
        $cursor = $from;
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $cursor->format('Y-m-d');
            $cursor = $cursor->add(new DateInterval('P1D'));
        }

        $revenueByDate = [];
        $cashNetByDate = [];
        $txCountByDate = [];
        foreach ($dates as $d) {
            $revenueByDate[$d] = 0;
            $cashNetByDate[$d] = 0;
            $txCountByDate[$d] = 0;
        }

        $paySplit = [
            'CASH' => 0,
            'TRANSFER' => 0,
            'N/A' => 0,
        ];

        /** @var array<string,list<array{ts:string,total:int}>> $perDayTx */
        $perDayTx = [];

        foreach ($tx as $r) {
            $d = (string) $r->business_date;
            if (! isset($revenueByDate[$d])) {
                continue;
            }

            $gt = (int) $r->grand_total;
            $revenueByDate[$d] += $gt;
            $txCountByDate[$d] += 1;

            $pm = $r->payment_method !== null ? (string) $r->payment_method : 'N/A';
            if (! isset($paySplit[$pm])) {
                $paySplit[$pm] = 0;
            }
            $paySplit[$pm] += $gt;

            if ($pm === 'CASH') {
                $recv = $r->cash_received !== null ? (int) $r->cash_received : null;
                $chg = $r->cash_change !== null ? (int) $r->cash_change : null;
                if ($recv !== null && $chg !== null) {
                    $cashNetByDate[$d] += ($recv - $chg);
                }
            }

            $ts = $r->completed_at !== null ? (string) $r->completed_at : (string) $r->id;
            if (! isset($perDayTx[$d])) {
                $perDayTx[$d] = [];
            }
            $perDayTx[$d][] = ['ts' => $ts, 'total' => $gt];
        }

        // Candlestick OHLC per day (only days with tx)
        $ohlcDaily = [];
        foreach ($perDayTx as $d => $rows) {
            usort($rows, static function (array $a, array $b): int {
                return $a['ts'] <=> $b['ts'];
            });

            $open = $rows[0]['total'];
            $close = $rows[count($rows) - 1]['total'];

            $high = $open;
            $low = $open;
            foreach ($rows as $row) {
                $high = max($high, $row['total']);
                $low = min($low, $row['total']);
            }

            $ohlcDaily[] = [
                'x' => $d,
                'y' => [$open, $high, $low, $close],
            ];
        }

        // Charts payload
        $revenueDaily = [];
        $cashNetDaily = [];
        foreach ($dates as $d) {
            $revenueDaily[] = ['date' => $d, 'value' => (int) $revenueByDate[$d]];
            $cashNetDaily[] = ['date' => $d, 'value' => (int) $cashNetByDate[$d]];
        }

        $payLabels = [];
        $paySeries = [];
        foreach (['CASH', 'TRANSFER', 'N/A'] as $k) {
            if (! isset($paySplit[$k])) {
                continue;
            }
            $payLabels[] = $k;
            $paySeries[] = (int) $paySplit[$k];
        }

        // KPI today (from computed buckets)
        $kpiRevenueToday = (int) ($revenueByDate[$todayStr] ?? 0);
        $kpiTxToday = (int) ($txCountByDate[$todayStr] ?? 0);
        $kpiCashNetToday = (int) ($cashNetByDate[$todayStr] ?? 0);

        // Purchases MTD
        $purchasesMtd = (int) DB::table('purchase_invoices')
            ->whereBetween('tgl_kirim', [$monthStartStr, $todayStr])
            ->sum('grand_total');

        // Expenses MTD
        $expensesMtd = (int) DB::table('expenses')
            ->whereBetween('expense_date', [$monthStartStr, $todayStr])
            ->sum('amount');

        // Low stock count & items
        $lowStockCount = (int) DB::table('inventory_stocks as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->where('p.is_active', '=', 1)
            ->whereRaw('(s.on_hand_qty - s.reserved_qty) <= p.min_stock_threshold')
            ->count();

        /** @var list<object> $lowItems */
        $lowItems = DB::table('inventory_stocks as s')
            ->join('products as p', 'p.id', '=', 's.product_id')
            ->where('p.is_active', '=', 1)
            ->select([
                'p.sku',
                'p.name',
                'p.min_stock_threshold',
                's.on_hand_qty',
                's.reserved_qty',
                DB::raw('(s.on_hand_qty - s.reserved_qty) AS available_qty'),
            ])
            ->whereRaw('(s.on_hand_qty - s.reserved_qty) <= p.min_stock_threshold')
            ->orderByRaw('(s.on_hand_qty - s.reserved_qty) ASC')
            ->limit(10)
            ->get()
            ->all();

        $lowStockItems = array_map(static function (object $r): array {
            return [
                'sku' => (string) $r->sku,
                'name' => (string) $r->name,
                'threshold' => (int) $r->min_stock_threshold,
                'on_hand' => (int) $r->on_hand_qty,
                'reserved' => (int) $r->reserved_qty,
                'available' => (int) $r->available_qty,
            ];
        }, $lowItems);

        // Recent purchases
        /** @var list<object> $rp */
        $rp = DB::table('purchase_invoices')
            ->orderByDesc('tgl_kirim')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'tgl_kirim', 'no_faktur', 'supplier_name', 'grand_total'])
            ->all();

        $recentPurchases = array_map(static function (object $r): array {
            return [
                'id' => (int) $r->id,
                'tgl_kirim' => (string) $r->tgl_kirim,
                'no_faktur' => (string) $r->no_faktur,
                'supplier_name' => (string) $r->supplier_name,
                'grand_total' => (int) $r->grand_total,
            ];
        }, $rp);

        // Recent audits
        /** @var list<object> $ra */
        $ra = DB::table('audit_logs')
            ->orderByDesc('id')
            ->limit(10)
            ->get([
                'id',
                'created_at',
                'actor_user_id',
                'action',
                'entity_type',
                'entity_id',
                'reason',
            ])->all();

        $recentAudits = array_map(static function (object $r): array {
            return [
                'id' => (int) $r->id,
                'created_at' => (string) $r->created_at,
                'actor_user_id' => $r->actor_user_id !== null ? (int) $r->actor_user_id : null,
                'action' => (string) $r->action,
                'entity_type' => (string) $r->entity_type,
                'entity_id' => $r->entity_id !== null ? (int) $r->entity_id : null,
                'reason' => $r->reason !== null ? (string) $r->reason : null,
            ];
        }, $ra);

        return [
            'kpi' => [
                'today' => [
                    'business_date' => $todayStr,
                    'revenue' => $kpiRevenueToday,
                    'tx_count' => $kpiTxToday,
                    'cash_net' => $kpiCashNetToday,
                ],
                'mtd' => [
                    'month_start' => $monthStartStr,
                    'purchases_total' => $purchasesMtd,
                    'expenses_total' => $expensesMtd,
                ],
                'low_stock_count' => $lowStockCount,
            ],
            'charts' => [
                'revenue_daily' => $revenueDaily,
                'cash_net_daily' => $cashNetDaily,
                'payment_split' => [
                    'labels' => $payLabels,
                    'series' => $paySeries,
                ],
                'ohlc_daily' => $ohlcDaily,
            ],
            'tables' => [
                'low_stock_items' => $lowStockItems,
                'recent_purchases' => $recentPurchases,
                'recent_audits' => $recentAudits,
            ],
        ];
    }
}
