<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoices', function (Blueprint $table) {
            $table->id();

            // Supplier (V1: string, no master)
            $table->string('supplier_name', 190)->index();

            // Header fields (from blueprint)
            $table->string('no_faktur', 64)->unique();
            $table->date('tgl_kirim')->index();

            // Payment / due date (enterprise reminders)
            $table->date('due_date')->nullable()->index();
            $table->string('payment_status', 16)->default('UNPAID')->index(); // UNPAID|PAID
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('paid_by_user_id')->nullable()->index();
            $table->string('paid_note', 255)->nullable();

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
            $table->index(['payment_status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoices');
    }
};