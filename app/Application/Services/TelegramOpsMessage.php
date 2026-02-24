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

    // -------- PUSH --------

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

    // -------- BOT --------

    public function botWelcome(): string
    {
        return implode("\n", [
            '🤖 '.$this->t('bot_title'),
            $this->t('bot_help'),
            '',
            $this->botCommands(),
        ]);
    }

    public function botNotLinked(): string
    {
        return implode("\n", [
            '⛔ '.$this->t('bot_not_linked_title'),
            $this->t('bot_not_linked_hint'),
        ]);
    }

    public function botLinkedOk(): string
    {
        return '✅ '.$this->t('bot_linked_ok');
    }

    public function botLinkFormatHint(): string
    {
        return $this->t('bot_link_format');
    }

    public function botAskInvoiceNo(): string
    {
        return '🧾 '.$this->t('bot_ask_invoice_no');
    }

    public function botInvoiceNotFound(string $noFaktur): string
    {
        return '❌ '.$this->t('bot_invoice_not_found').': '.$noFaktur;
    }

    public function botAskUploadProof(string $noFaktur): string
    {
        return implode("\n", [
            '📎 '.$this->t('bot_ask_upload'),
            $this->t('bot_invoice').': '.$noFaktur,
        ]);
    }

    public function botAskProductQuery(): string
    {
        return '🔎 '.$this->t('bot_ask_product_query');
    }

    public function botProductNotFound(string $q): string
    {
        return '❌ '.$this->t('bot_product_not_found').': '.$q;
    }

    public function botProofSubmittedPending(): string
    {
        return '✅ '.$this->t('bot_proof_pending');
    }

    public function botApproved(string $noFaktur): string
    {
        return '✅ '.$this->t('bot_approved').': '.$noFaktur;
    }

    public function botRejected(string $noFaktur, ?string $note): string
    {
        $msg = '❌ '.$this->t('bot_rejected').': '.$noFaktur;
        if ($note !== null && trim($note) !== '') {
            $msg .= "\n".$this->t('note').': '.trim($note);
        }

        return $msg;
    }

    private function botCommands(): string
    {
        $lines = $this->tLines('bot_commands_lines');

        return implode("\n", $lines);
    }

    private function moneyIdr(int $v): string
    {
        return 'Rp '.number_format($v, 0, ',', '.');
    }

    private function t(string $key): string
    {
        $dict = self::DICT[$this->locale] ?? self::DICT['id'];

        $v = $dict[$key] ?? $key;

        return is_string($v) ? $v : $key;
    }

    /**
     * @return list<string>
     */
    private function tLines(string $key): array
    {
        $dict = self::DICT[$this->locale] ?? self::DICT['id'];
        $v = $dict[$key] ?? [];

        if (! is_array($v)) {
            return [];
        }

        $out = [];
        foreach ($v as $line) {
            if (is_string($line) && trim($line) !== '') {
                $out[] = $line;
            }
        }

        return $out;
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

            'note' => 'Catatan',

            'bot_title' => 'BOT ADMIN',
            'bot_help' => 'Menu admin untuk cek hutang supplier, jatuh tempo, stok, & profit.',

            'bot_commands_lines' => [
                '/menu — tampilkan menu',
                '/laporan — alias menu',
                '/unpaid — daftar supplier belum dibayar',
                '/jatuh_tempo — invoice jatuh tempo H-5',
                '/overdue — invoice overdue',
                '/stok_menipis — daftar stok menipis',
                '/produk <q> — cari produk (SKU/Nama)',
                '/profit_latest — profit terakhir',
                '/pay — submit bukti bayar (akan diminta No Faktur)',
                '/link <TOKEN> — pairing bot ke akun admin',
            ],

            'bot_not_linked_title' => 'Belum terhubung',
            'bot_not_linked_hint' => 'Gunakan /link <TOKEN> dari halaman admin untuk pairing.',
            'bot_linked_ok' => 'Pairing berhasil. Ketik /menu.',
            'bot_link_format' => 'Format: /link <TOKEN>',
            'bot_ask_invoice_no' => 'Kirim No Faktur (contoh: FAK-001).',
            'bot_invoice_not_found' => 'Invoice tidak ditemukan',
            'bot_ask_upload' => 'Silakan upload foto/pdf bukti bayar.',
            'bot_invoice' => 'No Faktur',
            'bot_ask_product_query' => 'Kirim kata kunci produk (SKU/Nama).',
            'bot_product_not_found' => 'Produk tidak ditemukan',
            'bot_proof_pending' => 'Bukti bayar diterima. Status: PENDING (menunggu approve).',
            'bot_approved' => 'Pembayaran disetujui',
            'bot_rejected' => 'Pembayaran ditolak',
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

            'note' => 'Note',

            'bot_title' => 'ADMIN BOT',
            'bot_help' => 'Admin menu for payables, due, stock, and profit.',

            'bot_commands_lines' => [
                '/menu — show menu',
                '/laporan — menu alias',
                '/unpaid — unpaid supplier invoices',
                '/jatuh_tempo — due in 5 days',
                '/overdue — overdue invoices',
                '/stok_menipis — low stock items',
                '/produk <q> — search product (SKU/Name)',
                '/profit_latest — latest profit',
                '/pay — submit payment proof (invoice no will be requested)',
                '/link <TOKEN> — pair bot to admin account',
            ],

            'bot_not_linked_title' => 'Not linked',
            'bot_not_linked_hint' => 'Use /link <TOKEN> from admin page to pair.',
            'bot_linked_ok' => 'Paired successfully. Type /menu.',
            'bot_link_format' => 'Format: /link <TOKEN>',
            'bot_ask_invoice_no' => 'Send invoice number (e.g. FAK-001).',
            'bot_invoice_not_found' => 'Invoice not found',
            'bot_ask_upload' => 'Please upload a photo/pdf as payment proof.',
            'bot_invoice' => 'Invoice',
            'bot_ask_product_query' => 'Send product keyword (SKU/Name).',
            'bot_product_not_found' => 'Product not found',
            'bot_proof_pending' => 'Payment proof received. Status: PENDING (awaiting approval).',
            'bot_approved' => 'Payment approved',
            'bot_rejected' => 'Payment rejected',
        ],
    ];
}
