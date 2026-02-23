[asyraf@arch app]$ rg -n --hidden --glob '!.git/*' \
  "withErrors\(|->with\('success'|->with\('error'|session\(\)->flash|redirect\(\)->with" \
  app/Interfaces/Web/Controllers
app/Interfaces/Web/Controllers/Cashier/TransactionPartLineStoreController.php
40:                ->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Auth/LoginController.php
31:                ->withErrors(['email' => 'Email atau password salah.']);

app/Interfaces/Web/Controllers/Cashier/TransactionCompleteTransferController.php
30:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionPartLineUpdateQtyController.php
35:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionVoidController.php
34:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
38:            ->with('success', 'Transaksi berhasil di-VOID.');

app/Interfaces/Web/Controllers/Cashier/TransactionPartLineDeleteController.php
33:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionServiceLineUpdateController.php
37:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionServiceLineStoreController.php
40:                ->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionCompleteCashController.php
37:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Cashier/TransactionServiceLineDeleteController.php
33:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());

app/Interfaces/Web/Controllers/Admin/PurchaseInvoiceStoreController.php
97:                ->withErrors(['error' => $e->getMessage()]);
101:            ->with('success', 'Pembelian berhasil disimpan.');

app/Interfaces/Web/Controllers/Cashier/TransactionOpenController.php
39:            return redirect('/cashier/transactions/'.$transactionId)->with('error', $e->getMessage());
[asyraf@arch app]$ rg -n --hidden --glob '!.git/*' \
  "throw new \\\\InvalidArgumentException\(|\\\\InvalidArgumentException\('" \
  app/Application app/Interfaces
app/Application/UseCases/Payroll/ApplyLoanDeductionUseCase.php
21:            throw new \InvalidArgumentException('invalid actor user id');
24:            throw new \InvalidArgumentException('invalid payroll period id');
36:                throw new \InvalidArgumentException('payroll period not found');
39:                throw new \InvalidArgumentException('loan deductions already applied');
67:                    throw new \InvalidArgumentException('loan deduction exceeds total outstanding');

app/Application/UseCases/Payroll/CreateEmployeeLoanUseCase.php
27:                throw new \InvalidArgumentException('employee not found');
46:            throw new \InvalidArgumentException('invalid actor user id');
49:            throw new \InvalidArgumentException('invalid employee id');
53:            throw new \InvalidArgumentException('invalid loan_date format (expected Y-m-d)');
56:            throw new \InvalidArgumentException('amount must be > 0');
59:            throw new \InvalidArgumentException('note too long');

app/Application/UseCases/Payroll/UpdatePayrollPeriodUseCase.php
24:            throw new \InvalidArgumentException('invalid actor user id');
27:            throw new \InvalidArgumentException('invalid payroll period id');
32:            throw new \InvalidArgumentException('reason is required');
38:            throw new \InvalidArgumentException('invalid week_start format (expected Y-m-d)');
41:            throw new \InvalidArgumentException('invalid week_end format (expected Y-m-d)');
44:            throw new \InvalidArgumentException('week_start must be <= week_end');
48:            throw new \InvalidArgumentException('week_start must be Monday');
51:            throw new \InvalidArgumentException('week_end must be Saturday');
69:                throw new \InvalidArgumentException('payroll period not found');
117:                throw new \InvalidArgumentException('lines required');
124:                    throw new \InvalidArgumentException('invalid line payload');
127:                    throw new \InvalidArgumentException('invalid employee id');
130:                    throw new \InvalidArgumentException('gross_pay cannot be negative');
133:                    throw new \InvalidArgumentException('loan_deduction cannot be negative');
136:                    throw new \InvalidArgumentException('loan_deduction cannot exceed gross_pay');
139:                    throw new \InvalidArgumentException('zero line is not allowed');
147:                throw new \InvalidArgumentException('one or more employees not found');

app/Application/UseCases/Payroll/CreatePayrollPeriodUseCase.php
32:                throw new \InvalidArgumentException('payroll period already exists');
49:                    throw new \InvalidArgumentException('employee not found');
54:                    throw new \InvalidArgumentException('net pay cannot be negative');
83:            throw new \InvalidArgumentException('invalid actor user id');
89:            throw new \InvalidArgumentException('invalid week_start format (expected Y-m-d)');
92:            throw new \InvalidArgumentException('invalid week_end format (expected Y-m-d)');
98:            throw new \InvalidArgumentException('week_start must be Monday');
101:            throw new \InvalidArgumentException('week_end must be Saturday');
105:            throw new \InvalidArgumentException('payroll week must be Monday-Saturday (6 days)');
109:            throw new \InvalidArgumentException('note too long');
113:            throw new \InvalidArgumentException('lines required');
118:                throw new \InvalidArgumentException('invalid line payload');
121:                throw new \InvalidArgumentException('invalid employee id');
124:                throw new \InvalidArgumentException('gross_pay cannot be negative');
127:                throw new \InvalidArgumentException('loan_deduction cannot be negative');
130:                throw new \InvalidArgumentException('loan_deduction cannot exceed gross_pay');
133:                throw new \InvalidArgumentException('line note too long');
170:                throw new \InvalidArgumentException('loan deduction exceeds total outstanding');

app/Application/UseCases/Purchasing/CreatePurchaseInvoiceUseCase.php
40:                throw new \InvalidArgumentException('one or more products not found');
116:                    throw new \InvalidArgumentException('inventory stock not found for product');
125:                    throw new \InvalidArgumentException('product not found');
133:                    throw new \InvalidArgumentException('invalid on hand calculation');
171:            throw new \InvalidArgumentException('invalid actor user id');
174:            throw new \InvalidArgumentException('supplier name required');
177:            throw new \InvalidArgumentException('no_faktur required');
182:            throw new \InvalidArgumentException('invalid tgl_kirim format (expected Y-m-d)');
186:            throw new \InvalidArgumentException('total_pajak cannot be negative');
190:            throw new \InvalidArgumentException('lines required');
195:                throw new \InvalidArgumentException('invalid line payload');
198:                throw new \InvalidArgumentException('invalid product id');
201:                throw new \InvalidArgumentException('qty must be > 0');
204:                throw new \InvalidArgumentException('unit_cost cannot be negative');
207:                throw new \InvalidArgumentException('disc_bps must be within 0..10000');
236:                throw new \InvalidArgumentException('line net total cannot be negative');
251:            throw new \InvalidArgumentException('cannot allocate header tax when sum line net is zero');
311:            throw new \InvalidArgumentException('invalid denominator');

app/Application/UseCases/Purchasing/UpdatePurchaseInvoiceHeaderUseCase.php
24:            throw new \InvalidArgumentException('invalid actor user id');
27:            throw new \InvalidArgumentException('invalid purchase invoice id');
32:            throw new \InvalidArgumentException('supplier name required');
37:            throw new \InvalidArgumentException('no_faktur required');
42:            throw new \InvalidArgumentException('invalid tgl_kirim format (expected Y-m-d)');
47:            throw new \InvalidArgumentException('reason is required');
68:                throw new \InvalidArgumentException('purchase invoice not found');
77:                throw new \InvalidArgumentException('no_faktur already exists');

app/Application/UseCases/Inventory/ReleaseStockUseCase.php
30:            throw new \InvalidArgumentException('qty must be > 0');
35:            throw new \InvalidArgumentException('product not found');

app/Application/UseCases/Inventory/AdjustStockUseCase.php
33:            throw new \InvalidArgumentException('qtyDelta must not be 0');
38:            throw new \InvalidArgumentException('stock in is not allowed via adjustment; use purchases');
43:            throw new \InvalidArgumentException('note is required');
48:            throw new \InvalidArgumentException('product not found');

app/Application/UseCases/Inventory/ReserveStockUseCase.php
30:            throw new \InvalidArgumentException('qty must be > 0');
35:            throw new \InvalidArgumentException('product not found');

app/Application/UseCases/Notifications/NotifyLowStockForProductUseCase.php
25:            throw new \InvalidArgumentException('invalid product id');
28:            throw new \InvalidArgumentException('triggerType is required');

app/Application/UseCases/Expenses/CreateExpenseUseCase.php
40:            throw new \InvalidArgumentException('invalid actor user id');
45:            throw new \InvalidArgumentException('invalid expense_date format (expected Y-m-d)');
50:            throw new \InvalidArgumentException('invalid category');
54:            throw new \InvalidArgumentException('amount cannot be negative');
58:            throw new \InvalidArgumentException('note too long');

app/Application/UseCases/Sales/UpdatePartLineQtyUseCase.php
29:            throw new \InvalidArgumentException('qty must be >= 1');
34:            throw new \InvalidArgumentException('reason is required');
40:                throw new \InvalidArgumentException('transaction not found');
45:                throw new \InvalidArgumentException('cannot edit part lines unless DRAFT/OPEN');
50:                throw new \InvalidArgumentException('actor user not found');
60:                throw new \InvalidArgumentException('part line not found');
89:                    throw new \InvalidArgumentException('insufficient available stock');
108:                    throw new \InvalidArgumentException('reserved stock insufficient');

app/Application/UseCases/Catalog/SetMinStockThresholdUseCase.php
22:            throw new \InvalidArgumentException('note is required');
26:            throw new \InvalidArgumentException('minStockThreshold must be >= 0');

app/Application/UseCases/Catalog/UpdateProductUseCase.php
24:            throw new \InvalidArgumentException('sku is required');
28:            throw new \InvalidArgumentException('name is required');

app/Application/UseCases/Catalog/SetSellingPriceUseCase.php
25:            throw new \InvalidArgumentException('note is required');
29:            throw new \InvalidArgumentException('sellPriceCurrent must be >= 0');

app/Application/UseCases/Sales/DeleteServiceLineUseCase.php
25:            throw new \InvalidArgumentException('reason is required');
33:                throw new \InvalidArgumentException('transaction not found');
40:                throw new \InvalidArgumentException('transaction not editable');
44:                throw new \InvalidArgumentException('transaction not editable');
49:                throw new \InvalidArgumentException('actor user not found');
53:                throw new \InvalidArgumentException('cashier cannot edit different business date');
63:                throw new \InvalidArgumentException('service line not found');

app/Application/UseCases/Sales/DeletePartLineUseCase.php
30:            throw new \InvalidArgumentException('reason is required');
36:                throw new \InvalidArgumentException('transaction not found');
41:                throw new \InvalidArgumentException('cannot delete part lines unless DRAFT/OPEN');
46:                throw new \InvalidArgumentException('actor user not found');
56:                throw new \InvalidArgumentException('part line not found');
65:                throw new \InvalidArgumentException('reserved stock insufficient');

app/Application/UseCases/Sales/AddPartLineUseCase.php
34:            throw new \InvalidArgumentException('qty must be positive');
39:            throw new \InvalidArgumentException('reason is required');
47:                throw new \InvalidArgumentException('transaction not found');
54:                throw new \InvalidArgumentException('transaction not editable');
57:                throw new \InvalidArgumentException('transaction not editable');
62:                throw new \InvalidArgumentException('actor user not found');
65:                throw new \InvalidArgumentException('cashier cannot edit different business date');

app/Application/UseCases/Sales/CompleteTransactionUseCase.php
26:            throw new \InvalidArgumentException('invalid payment method');
38:                throw new \InvalidArgumentException('transaction not found');
45:                throw new \InvalidArgumentException('transaction not completable');
49:                throw new \InvalidArgumentException('cannot complete transaction for different business date');
80:                    throw new \InvalidArgumentException('cash received insufficient');
107:                    throw new \InvalidArgumentException('inventory stock not found');
114:                    throw new \InvalidArgumentException('reserved stock insufficient at completion');
117:                    throw new \InvalidArgumentException('on hand stock insufficient at completion');

app/Application/UseCases/Sales/OpenTransactionUseCase.php
26:                throw new \InvalidArgumentException('transaction not found');
35:                throw new \InvalidArgumentException('only DRAFT/OPEN can be updated');

app/Application/UseCases/Sales/RemovePartLineUseCase.php
30:            throw new \InvalidArgumentException('reason is required');
38:                throw new \InvalidArgumentException('transaction not found');
45:                throw new \InvalidArgumentException('transaction not editable');
48:                throw new \InvalidArgumentException('transaction not editable');
53:                throw new \InvalidArgumentException('actor user not found');
56:                throw new \InvalidArgumentException('cashier cannot edit different business date');
66:                throw new \InvalidArgumentException('part line not found');

app/Application/UseCases/Sales/AddServiceLineUseCase.php
27:            throw new \InvalidArgumentException('description is required');
31:            throw new \InvalidArgumentException('priceManual must be >= 0');
36:            throw new \InvalidArgumentException('reason is required');
44:                throw new \InvalidArgumentException('transaction not found');
51:                throw new \InvalidArgumentException('transaction not editable');
54:                throw new \InvalidArgumentException('transaction not editable');
59:                throw new \InvalidArgumentException('actor user not found');
62:                throw new \InvalidArgumentException('cashier cannot edit different business date');

app/Application/UseCases/Sales/UpdateServiceLineUseCase.php
27:            throw new \InvalidArgumentException('description is required');
31:            throw new \InvalidArgumentException('priceManual must be >= 0');
37:            throw new \InvalidArgumentException('reason is required');
45:                throw new \InvalidArgumentException('transaction not found');
53:                throw new \InvalidArgumentException('transaction not editable');
57:                throw new \InvalidArgumentException('transaction not editable');
62:                throw new \InvalidArgumentException('actor user not found');
68:                throw new \InvalidArgumentException('cashier cannot edit different business date');
78:                throw new \InvalidArgumentException('service line not found');

app/Application/UseCases/Sales/VoidTransactionUseCase.php
28:            throw new \InvalidArgumentException('reason is required');
41:                throw new \InvalidArgumentException('transaction not found');
48:                throw new \InvalidArgumentException('transaction not voidable');
53:                throw new \InvalidArgumentException('actor user not found');
56:                throw new \InvalidArgumentException('cashier cannot void different business date');
95:                    throw new \InvalidArgumentException('inventory stock not found');
129:                    throw new \InvalidArgumentException('reserved stock insufficient at void');

app/Application/UseCases/Catalog/CreateProductUseCase.php
26:            throw new \InvalidArgumentException('sku is required');
30:            throw new \InvalidArgumentException('name is required');
34:            throw new \InvalidArgumentException('sellPriceCurrent must be >= 0');
38:            throw new \InvalidArgumentException('minStockThreshold must be >= 0');
[asyraf@arch app]$ rg -n --hidden --glob '!.git/*' \
  "alert-|Validasi error|berhasil|gagal|Error|Simpan|Batal|Kembali|Tambah|Edit|Hapus" \
  resources/views
resources/views/admin/purchases/create.blade.php
3:@section('title', 'Tambah Pembelian')
8:            <h3>Tambah Pembelian (Supplier)</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Kembali</a>
21:        <div class="alert alert-danger">
22:            <div class="fw-bold mb-2">Validasi error</div>
136:                            <button class="btn btn-primary" type="submit">Simpan Pembelian</button>
137:                            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">Batal</a>

resources/views/admin/purchases/edit.blade.php
3:@section('title', 'Edit Pembelian')
8:            <h3>Edit Pembelian (Header)</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases/'.$invoice->id) }}">← Kembali</a>
19:    <div class="alert alert-warning">
21:        <div>Edit hanya untuk <span class="fw-semibold">header</span> (metadata). Line (qty/unit_cost/diskon) tidak diedit agar stok & avg_cost tetap konsisten.</div>
25:        <div class="alert alert-danger">
26:            <div class="fw-bold mb-2">Validasi error</div>
83:                        <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
84:                        <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases/'.$invoice->id) }}">Batal</a>

resources/views/auth/login.blade.php
39:                        <div class="alert alert-danger shadow-sm">

resources/views/admin/employees/create.blade.php
3:@section('title', 'Tambah Karyawan')
8:            <h3>Tambah Karyawan</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>
50:                        <button class="btn btn-primary" type="submit">Simpan</button>
51:                        <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Batal</a>

resources/views/admin/purchases/index.blade.php
13:            <a class="btn btn-primary" href="{{ url('/admin/purchases/create') }}">Tambah Pembelian</a>

resources/views/admin/purchases/show.blade.php
15:            <a class="btn btn-outline-primary" href="{{ url('/admin/purchases/'.$invoice->id.'/edit') }}">Edit Header</a>
16:            <a class="btn btn-outline-secondary" href="{{ url('/admin/purchases') }}">← Kembali</a>

resources/views/admin/employees/index.blade.php
13:            <a class="btn btn-primary" href="{{ url('/admin/employees/create') }}">Tambah Karyawan</a>

resources/views/components/ui/alert.blade.php
6:<div {{ $attributes->merge(['class' => 'alert alert-'.$type]) }} role="alert">

resources/views/admin/payroll/create.blade.php
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>
106:                            <button class="btn btn-primary" type="submit">Simpan Payroll</button>
107:                            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">Batal</a>

resources/views/admin/employee_loans/create.blade.php
3:@section('title', 'Tambah Loan')
8:            <h3>Tambah Pinjaman Buat {{ $employee->name }}</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>
52:                        <button class="btn btn-primary" type="submit">Simpan Pinjaman</button>
53:                        <a class="btn btn-outline-secondary" href="{{ url('/admin/employees') }}">Batal</a>

resources/views/admin/products/create.blade.php
3:@section('title', 'Tambah Produk')
8:            <h3>Tambah Produk</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>
49:                        <div class="form-text">Simpan sebagai angka bulat (rupiah).</div>
66:                        <button class="btn btn-primary" type="submit">Simpan</button>
67:                        <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">Batal</a>

resources/views/admin/payroll/show.blade.php
21:            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll') }}">← Kembali</a>
23:                <a class="btn btn-outline-primary" href="{{ url('/admin/payroll/'.$period->id.'/edit') }}">Edit</a>
115:                        <div class="alert alert-warning mt-3 mb-0">
116:                            Period sudah <b>Applied</b>. Edit lines tidak diperbolehkan agar laporan & potongan hutang konsisten.

resources/views/admin/payroll/edit.blade.php
3:@section('title', 'Edit Payroll')
10:            <h3>Edit Payroll Period</h3>
15:            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll/'.$period->id) }}">← Kembali</a>
27:        <div class="alert alert-warning">
28:            Period sudah <b>Applied</b> ({{ $period->loan_deductions_applied_at }}). Edit lines tidak diperbolehkan. Hanya note header yang bisa diubah.
33:        <div class="alert alert-danger">
34:            <div class="fw-bold mb-2">Validasi error</div>
143:                            <button class="btn btn-primary" type="submit">Simpan Perubahan</button>
144:                            <a class="btn btn-outline-secondary" href="{{ url('/admin/payroll/'.$period->id) }}">Batal</a>

resources/views/cashier/transactions/partials/_cash_calculator.blade.php
38:                <div>Kembalian: <b><x-ui.rupiah :value="$cashChange" /></b></div>
60:                Pembayaran hanya tersedia saat nota <b>OPEN</b>. Silakan klik <b>Simpan Nota</b> terlebih dahulu.
78:                <div class="text-muted">Kembalian</div>

resources/views/cashier/transactions/partials/_part_lines.blade.php
66:                                        <input type="hidden" name="reason" value="Hapus line {{ $l->sku }}">
71:                                                title="Hapus"
72:                                                aria-label="Hapus">

resources/views/admin/expenses/create.blade.php
3:@section('title', 'Tambah Operasional')
8:            <h3>Tambah Operasional</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/expenses') }}">Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>
57:                        <button class="btn btn-primary" type="submit">Simpan</button>
58:                        <a class="btn btn-outline-secondary" href="{{ url('/admin/expenses') }}">Batal</a>

resources/views/cashier/transactions/partials/_customer_form.blade.php
3:    $canEdit = in_array($status, ['DRAFT', 'OPEN'], true);
12:        @if (!$canEdit)

resources/views/admin/payroll/index.blade.php
65:                                    <a class="btn btn-sm btn-outline-secondary" href="{{ url('/admin/payroll/'.$p->id.'/edit') }}">Edit</a>

resources/views/admin/products/edit.blade.php
3:@section('title', 'Edit Produk')
8:            <h3>Edit Produk</h3>
13:            <a class="btn btn-outline-secondary" href="{{ url('/admin/products') }}">← Kembali</a>
20:        <div class="alert alert-danger">
21:            <div class="fw-bold mb-2">Validasi error</div>

resources/views/admin/expenses/index.blade.php
13:            <a class="btn btn-primary" href="{{ url('/admin/expenses/create') }}">Tambah Operasional</a>

resources/views/admin/products/index.blade.php
13:            <a class="btn btn-primary" href="{{ url('/admin/products/create') }}">Tambah Produk</a>
90:                                    Edit

resources/views/cashier/transactions/partials/_service_lines.blade.php
6:        <h6>Tambah Service</h6>
10:            <input type="hidden" name="reason" value="Tambah service">
23:                <button type="submit" class="btn btn-outline-primary">Tambah</button>
89:                                        <input type="hidden" name="reason" value="Hapus service #{{ $s->id }}">
94:                                                title="Hapus"
95:                                                aria-label="Hapus"
96:                                                onclick="return confirm('Hapus service ini?')">

resources/views/admin/dashboard/index.blade.php
240:                        <a class="btn btn-outline-primary" href="{{ url('/admin/products/create') }}">Tambah Produk</a>

resources/views/cashier/transactions/show.blade.php
16:                Kembali

resources/views/cashier/transactions/partials/_show_scripts.blade.php
76:            throw new Error('fragment fetch failed');

resources/views/cashier/transactions/partials/_alerts.blade.php
3:        <b>Error:</b> {{ session('error') }}

resources/views/cashier/transactions/partials/_product_search.blade.php
104:        if (!res.ok) throw new Error('fetch rows failed');

resources/views/cashier/transactions/partials/_today_scripts.blade.php
46:            if (!res.ok) throw new Error('HTTP ' + res.status);
54:            if (!root) throw new Error('Fragment root not found');

resources/views/cashier/products/partials/_rows.blade.php
38:                            <input type="hidden" name="reason" value="Tambah sparepart {{ $p->sku }}">
41:                                Tambah

resources/views/cashier/products/search.blade.php
13:            <a href="{{ url('/cashier/dashboard') }}" class="btn btn-light">Kembali</a>

resources/views/cashier/transactions/partials/_summary_actions.blade.php
26:                <div class="alert alert-light">
41:                                <span>Simpan Nota</span>
[asyraf@arch app]$ rg -n --hidden --glob '!.git/*' \
  "NotifyLowStock|low stock|stock|stok|notification|notifikasi" \
  app
app/Application/UseCases/Catalog/CreateProductUseCase.php
17:        private InventoryStockRepositoryPort $stocks,
51:            // ensure stock row exists (0/0)
52:            $this->stocks->lockOrCreateByProductId($product->id);

app/Infrastructure/Notifications/Telegram/TelegramLowStockNotifier.php
16:        $enabled = (bool) config('services.telegram_low_stock.enabled', false);
21:        $token = (string) config('services.telegram_low_stock.bot_token', '');
26:        $chatIdsRaw = (string) config('services.telegram_low_stock.chat_ids', '');
44:                    Log::warning('telegram_low_stock_send_failed', [
50:                Log::warning('telegram_low_stock_send_exception', [

app/Application/UseCases/Notifications/NotifyLowStockForProductRequest.php
7:final readonly class NotifyLowStockForProductRequest

app/Application/UseCases/Purchasing/CreatePurchaseInvoiceUseCase.php
9:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
10:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
18:        private ?NotifyLowStockForProductUseCase $lowStock = null,
80:                DB::table('stock_ledgers')->insert([
88:                    'note' => 'purchase invoice stock in',
110:                $stock = DB::table('inventory_stocks')
115:                if ($stock === null) {
116:                    throw new \InvalidArgumentException('inventory stock not found for product');
128:                $oldOnHand = (int) $stock->on_hand_qty;
139:                DB::table('inventory_stocks')->where('product_id', $productId)->update([
159:            $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Application/UseCases/Notifications/NotifyLowStockForProductUseCase.php
14:final readonly class NotifyLowStockForProductUseCase
22:    public function handle(NotifyLowStockForProductRequest $req): void
41:    private function handleInTx(NotifyLowStockForProductRequest $req): void
48:            ->first(['id', 'sku', 'name', 'min_stock_threshold', 'is_active']);
58:        $stock = DB::table('inventory_stocks')
63:        if ($stock === null) {
67:        $onHand = (int) $stock->on_hand_qty;
68:        $reserved = (int) $stock->reserved_qty;
71:        $threshold = (int) $product->min_stock_threshold;
73:        $resetOnRecover = (bool) config('services.telegram_low_stock.reset_on_recover', true);
77:                DB::table('low_stock_notification_states')
85:        $state = DB::table('low_stock_notification_states')
92:                DB::table('low_stock_notification_states')->insert([
103:            $state = DB::table('low_stock_notification_states')
109:        $minIntervalSeconds = (int) config('services.telegram_low_stock.min_interval_seconds', 86400);
153:        $throttleOnFailure = (bool) config('services.telegram_low_stock.throttle_on_failure', true);
156:            DB::table('low_stock_notification_states')

app/Application/UseCases/AdminDashboard/GetAdminDashboardUseCase.php
32:     *     low_stock_count: int
41:     *     low_stock_items: list<array{sku:string,name:string,threshold:int,on_hand:int,reserved:int,available:int}>,
212:        // Low stock count & items
213:        $lowStockCount = (int) DB::table('inventory_stocks as s')
216:            ->whereRaw('(s.on_hand_qty - s.reserved_qty) <= p.min_stock_threshold')
220:        $lowItems = DB::table('inventory_stocks as s')
226:                'p.min_stock_threshold',
231:            ->whereRaw('(s.on_hand_qty - s.reserved_qty) <= p.min_stock_threshold')
241:                'threshold' => (int) $r->min_stock_threshold,
307:                'low_stock_count' => $lowStockCount,
319:                'low_stock_items' => $lowStockItems,

app/Application/UseCases/Sales/DeletePartLineUseCase.php
21:        private InventoryStockRepositoryPort $stocks,
62:            $stock = $this->stocks->lockOrCreateByProductId($productId);
64:            if ($stock->reservedQty < $qty) {
65:                throw new \InvalidArgumentException('reserved stock insufficient');
71:                'stock' => [
73:                    'on_hand_qty' => $stock->onHandQty,
74:                    'reserved_qty' => $stock->reservedQty,
75:                    'available_qty' => $stock->availableQty(),
80:            $this->stocks->save($stock->withReservedQty($stock->reservedQty - $qty));
96:            $stockAfter = $this->stocks->lockOrCreateByProductId($productId);
101:                'stock' => [
103:                    'on_hand_qty' => $stockAfter->onHandQty,
104:                    'reserved_qty' => $stockAfter->reservedQty,
105:                    'available_qty' => $stockAfter->availableQty(),

app/Interfaces/Web/Controllers/Cashier/TransactionShowController.php
55:            ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
70:                'inventory_stocks.on_hand_qty',
71:                'inventory_stocks.reserved_qty',
89:            ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
104:                'inventory_stocks.on_hand_qty',
105:                'inventory_stocks.reserved_qty',
106:                \Illuminate\Support\Facades\DB::raw('(inventory_stocks.on_hand_qty - inventory_stocks.reserved_qty) as available_qty'),

app/Application/UseCases/Inventory/ReleaseStockUseCase.php
13:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
14:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
22:        private InventoryStockRepositoryPort $stocks,
24:        private ?NotifyLowStockForProductUseCase $lowStock = null,
39:            $stock = $this->stocks->lockOrCreateByProductId($req->productId);
41:            if ($stock->reservedQty < $req->qty) {
45:                    currentReservedQty: $stock->reservedQty,
49:            $newReserved = $stock->reservedQty - $req->qty;
50:            $this->stocks->save($stock->withReservedQty($newReserved));
68:        $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Application/UseCases/Inventory/InsufficientStock.php
16:        parent::__construct("Insufficient stock for product_id={$productId}. requested={$requestedQty}, available={$availableQty}");

app/Application/UseCases/Sales/VoidTransactionUseCase.php
10:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
11:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
20:        private NotifyLowStockForProductUseCase $lowStock,
89:                $stock = DB::table('inventory_stocks')
94:                if ($stock === null) {
95:                    throw new \InvalidArgumentException('inventory stock not found');
98:                $beforeStocks[(string) $productId] = (array) $stock;
100:                $onHand = (int) $stock->on_hand_qty;
101:                $reserved = (int) $stock->reserved_qty;
104:                    DB::table('inventory_stocks')->where('product_id', $productId)->update([
109:                    DB::table('stock_ledgers')->insert([
129:                    throw new \InvalidArgumentException('reserved stock insufficient at void');
132:                DB::table('inventory_stocks')->where('product_id', $productId)->update([
137:                DB::table('stock_ledgers')->insert([
187:                $stocksAfterRows = DB::table('inventory_stocks')->whereIn('product_id', $pids)->get()->all();
188:                foreach ($stocksAfterRows as $row) {
197:                'stocks' => $beforeStocks,
204:                'stocks' => $afterStocks,
230:            $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Interfaces/Web/Controllers/Cashier/ProductSearchController.php
29:                ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
42:                    'inventory_stocks.on_hand_qty',
43:                    'inventory_stocks.reserved_qty',
68:                ->join('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
81:                    'inventory_stocks.on_hand_qty',
82:                    'inventory_stocks.reserved_qty',
83:                    DB::raw('(inventory_stocks.on_hand_qty - inventory_stocks.reserved_qty) as available_qty'),

app/Application/UseCases/Sales/AddPartLineUseCase.php
77:            $beforeStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->lockForUpdate()->first();
82:                'stock' => $beforeStock ? (array) $beforeStock : null,
119:            $afterStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->first();
124:                'stock' => $afterStock ? (array) $afterStock : null,

app/Application/UseCases/Inventory/ReserveStockUseCase.php
13:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
14:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
22:        private InventoryStockRepositoryPort $stocks,
24:        private ?NotifyLowStockForProductUseCase $lowStock = null,
39:            $stock = $this->stocks->lockOrCreateByProductId($req->productId);
41:            $available = $stock->availableQty();
50:            $newReserved = $stock->reservedQty + $req->qty;
51:            $this->stocks->save($stock->withReservedQty($newReserved));
69:        $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Application/Ports/Repositories/InventoryStockRepositoryPort.php
15:     * If stock row does not exist yet for the product, it must be created then locked.
23:    public function save(InventoryStockSnapshot $stock): void;

app/Application/UseCases/Inventory/AdjustStockUseCase.php
14:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
15:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
24:        private InventoryStockRepositoryPort $stocks,
27:        private ?NotifyLowStockForProductUseCase $lowStock = null,
36:        // POLICY: stok masuk (qtyDelta > 0) hanya lewat Purchases
38:            throw new \InvalidArgumentException('stock in is not allowed via adjustment; use purchases');
52:            $stock = $this->stocks->lockOrCreateByProductId($req->productId);
56:                'on_hand_qty' => $stock->onHandQty,
57:                'reserved_qty' => $stock->reservedQty,
58:                'available_qty' => $stock->availableQty(),
61:            $newOnHand = $stock->onHandQty + $req->qtyDelta;
66:                    currentOnHandQty: $stock->onHandQty,
70:            $this->stocks->save($stock->withOnHandQty($newOnHand));
86:                'reserved_qty' => $stock->reservedQty,
87:                'available_qty' => $newOnHand - $stock->reservedQty,
94:                entityId: $req->productId, // inventory_stocks pk = product_id
111:        $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Interfaces/Web/Controllers/Admin/ProductSetThresholdController.php
17:            'min_stock_threshold' => ['required', 'integer', 'min:0'],
23:            minStockThreshold: (int) $data['min_stock_threshold'],

app/Interfaces/Web/Controllers/Admin/ProductStoreController.php
20:            'min_stock_threshold' => ['required', 'integer', 'min:0'],
28:            minStockThreshold: (int) $data['min_stock_threshold'],

app/Interfaces/Web/Controllers/Admin/StockReportIndexController.php
30:        return view('admin.reports.stock.index', [

app/Infrastructure/Persistence/Eloquent/Models/InventoryStock.php
12:    protected $table = 'inventory_stocks';

app/Infrastructure/Persistence/Eloquent/Models/Product.php
21:        'min_stock_threshold',
33:            'min_stock_threshold' => 'int',
39:    public function stock(): HasOne

app/Application/UseCases/Sales/CompleteTransactionUseCase.php
10:use App\Application\UseCases\Notifications\NotifyLowStockForProductRequest;
11:use App\Application\UseCases\Notifications\NotifyLowStockForProductUseCase;
20:        private NotifyLowStockForProductUseCase $lowStock,
101:                $stock = DB::table('inventory_stocks')
106:                if ($stock === null) {
107:                    throw new \InvalidArgumentException('inventory stock not found');
110:                $onHand = (int) $stock->on_hand_qty;
111:                $reserved = (int) $stock->reserved_qty;
114:                    throw new \InvalidArgumentException('reserved stock insufficient at completion');
117:                    throw new \InvalidArgumentException('on hand stock insufficient at completion');
120:                DB::table('inventory_stocks')->where('product_id', $productId)->update([
126:                DB::table('stock_ledgers')->insert([
139:                DB::table('stock_ledgers')->insert([
180:            $this->lowStock->handle(new NotifyLowStockForProductRequest(

app/Application/UseCases/Sales/UpdatePartLineQtyUseCase.php
21:        private InventoryStockRepositoryPort $stocks,
72:            $stock = $this->stocks->lockOrCreateByProductId($productId);
77:                'stock' => [
79:                    'on_hand_qty' => $stock->onHandQty,
80:                    'reserved_qty' => $stock->reservedQty,
81:                    'available_qty' => $stock->availableQty(),
88:                if ($stock->availableQty() < $delta) {
89:                    throw new \InvalidArgumentException('insufficient available stock');
92:                $this->stocks->save($stock->withReservedQty($stock->reservedQty + $delta));
107:                if ($stock->reservedQty < $releaseQty) {
108:                    throw new \InvalidArgumentException('reserved stock insufficient');
111:                $this->stocks->save($stock->withReservedQty($stock->reservedQty - $releaseQty));
139:            $stockAfter = $this->stocks->lockOrCreateByProductId($productId);
144:                'stock' => [
146:                    'on_hand_qty' => $stockAfter->onHandQty,
147:                    'reserved_qty' => $stockAfter->reservedQty,
148:                    'available_qty' => $stockAfter->availableQty(),

app/Interfaces/Web/Controllers/Admin/ProductAdjustStockController.php
17:            // POLICY: hanya koreksi pengurangan (stok masuk wajib lewat purchases)

app/Application/UseCases/Sales/RemovePartLineUseCase.php
71:            $beforeStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->lockForUpdate()->first();
76:                'stock' => $beforeStock ? (array) $beforeStock : null,
95:            $afterStock = DB::table('inventory_stocks')->where('product_id', $req->productId)->first();
100:                'stock' => $afterStock ? (array) $afterStock : null,

app/Infrastructure/Persistence/Eloquent/Repositories/EloquentInventoryStockRepository.php
17:        $stock = InventoryStock::query()
22:        if ($stock === null) {
33:            $stock = InventoryStock::query()
40:            id: (int) $stock->id,
41:            productId: (int) $stock->product_id,
42:            onHandQty: (int) $stock->on_hand_qty,
43:            reservedQty: (int) $stock->reserved_qty,
47:    public function save(InventoryStockSnapshot $stock): void
50:            ->whereKey($stock->id)
52:                'on_hand_qty' => $stock->onHandQty,
53:                'reserved_qty' => $stock->reservedQty,

app/Infrastructure/Persistence/Eloquent/Repositories/EloquentProductRepository.php
26:            minStockThreshold: (int) $p->min_stock_threshold,
44:            'min_stock_threshold' => $minStockThreshold,
54:            minStockThreshold: (int) $p->min_stock_threshold,
77:            'min_stock_threshold' => $minStockThreshold,

app/Infrastructure/Persistence/Eloquent/Models/StockLedger.php
12:    protected $table = 'stock_ledgers';

app/Infrastructure/Persistence/Eloquent/Repositories/EloquentProductStockQuery.php
16:            ->leftJoin('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
22:                'products.min_stock_threshold',
24:                DB::raw('COALESCE(inventory_stocks.on_hand_qty, 0) as on_hand_qty'),
25:                DB::raw('COALESCE(inventory_stocks.reserved_qty, 0) as reserved_qty'),
52:                minStockThreshold: (int) $r->min_stock_threshold,
65:            ->leftJoin('inventory_stocks', 'inventory_stocks.product_id', '=', 'products.id')
71:                'products.min_stock_threshold',
73:                DB::raw('COALESCE(inventory_stocks.on_hand_qty, 0) as on_hand_qty'),
74:                DB::raw('COALESCE(inventory_stocks.reserved_qty, 0) as reserved_qty'),
88:            minStockThreshold: (int) $r->min_stock_threshold,

app/Interfaces/Web/Controllers/Admin/StockReportPdfController.php
31:        $bytes = $pdf->renderBlade('admin.reports.stock.pdf', [
41:        $filename = 'stock-report.pdf';

app/Infrastructure/Persistence/Eloquent/Repositories/EloquentStockReportQuery.php
21:            ->leftJoin('inventory_stocks as s', 's.product_id', '=', 'p.id')
42:            'p.min_stock_threshold',
56:            $threshold = (int) $r->min_stock_threshold;
[asyraf@arch app]$ 