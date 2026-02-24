<?php

declare(strict_types=1);

namespace App\Application\Services;

use App\Application\DTO\Reporting\ProfitReportRow;

final readonly class TelegramOpsMessage
{
    public function __construct(
        private string $locale = 'id',
    ) {}

    public static function fromConfig(): self
    {
        $loc = (string) config('services.telegram_ops.locale', 'id');
        $loc = strtolower(trim($loc));
        if (! in_array($loc, ['id', 'en'], true)) {
            $loc = 'id';
        }

        return new self($loc);
    }

    public function profitDaily(string $date, ?ProfitReportRow $row): string
    {
        if ($row === null) {
            return implode("\n", [
                '📈 '.$this->t('profit_title'),
                $this->t('date').': '.$date,
                $this->t('data_empty'),
            ]);
        }

        $money = fn (int $v): string => $this->moneyIdr($v);

        $lines = [
            '📈 '.$this->t('profit_title'),
            $this->t('date').': '.$date,
            $this->t('revenue').': '.$money((int) $row->revenueTotal),
            $this->t('cogs').': '.$money((int) $row->cogsTotal),
            $this->t('expenses').': '.$money((int) $row->expensesTotal),
            $this->t('payroll').': '.$money((int) $row->payrollGross),
            $this->t('net').': '.$money((int) $row->netProfit),
        ];

        $missing = (int) $row->missingCogsQty;
        if ($missing > 0) {
            $lines[] = '⚠️ '.$this->t('missing_cogs_qty').': '.$missing;
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<object>  $rows
     */
    public function purchaseDueH5Digest(string $dueDate, array $rows): string
    {
        $money = fn (int $v): string => $this->moneyIdr($v);

        $lines = [
            '⏳ '.$this->t('due_h5_title'),
            $this->t('due_date').': '.$dueDate,
            $this->t('count').': '.count($rows),
            '',
        ];

        foreach ($rows as $r) {
            $lines[] = implode(' | ', [
                (string) $r->no_faktur,
                (string) $r->supplier_name,
                $this->t('ship_date_short').': '.(string) $r->tgl_kirim,
                $this->t('total_short').': '.$money((int) $r->grand_total),
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * @param  list<object>  $rows
     */
    public function purchaseOverdueDigest(string $today, array $rows): string
    {
        $money = fn (int $v): string => $this->moneyIdr($v);

        $lines = [
            '🚨 '.$this->t('overdue_title'),
            $this->t('date').': '.$today,
            $this->t('count').': '.count($rows),
            '',
        ];

        foreach ($rows as $r) {
            $lines[] = implode(' | ', [
                (string) $r->no_faktur,
                (string) $r->supplier_name,
                $this->t('due_date_short').': '.(string) $r->due_date,
                $this->t('total_short').': '.$money((int) $r->grand_total),
            ]);
        }

        return implode("\n", $lines);
    }

    private function moneyIdr(int $v): string
    {
        // keep Indonesian thousands separator style as you requested: 15.000
        $n = number_format($v, 0, ',', '.');

        // In EN mode, keep "Rp" prefix too (finance context), but you can change here.
        return 'Rp '.$n;
    }

    private function t(string $key): string
    {
        $dict = self::DICT[$this->locale] ?? self::DICT['id'];

        return $dict[$key] ?? $key;
    }

    private const DICT = [
        'id' => [
            'profit_title' => 'PROFIT HARIAN',
            'due_h5_title' => 'JATUH TEMPO H-5',
            'overdue_title' => 'OVERDUE PEMBELIAN',

            'date' => 'Tanggal',
            'due_date' => 'Due date',
            'count' => 'Jumlah',
            'data_empty' => 'Data: (kosong)',

            'revenue' => 'Revenue',
            'cogs' => 'COGS',
            'expenses' => 'Expenses',
            'payroll' => 'Payroll',
            'net' => 'Net',
            'missing_cogs_qty' => 'Missing COGS Qty',

            'ship_date_short' => 'Kirim',
            'due_date_short' => 'Due',
            'total_short' => 'Total',
        ],
        'en' => [
            'profit_title' => 'DAILY PROFIT',
            'due_h5_title' => 'DUE IN 5 DAYS',
            'overdue_title' => 'OVERDUE PURCHASES',

            'date' => 'Date',
            'due_date' => 'Due date',
            'count' => 'Count',
            'data_empty' => 'Data: (empty)',

            'revenue' => 'Revenue',
            'cogs' => 'COGS',
            'expenses' => 'Expenses',
            'payroll' => 'Payroll',
            'net' => 'Net',
            'missing_cogs_qty' => 'Missing COGS Qty',

            'ship_date_short' => 'Ship',
            'due_date_short' => 'Due',
            'total_short' => 'Total',
        ],
    ];
}