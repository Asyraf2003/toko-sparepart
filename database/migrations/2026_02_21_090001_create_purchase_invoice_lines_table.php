<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_invoice_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('purchase_invoice_id')
                ->constrained('purchase_invoices')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->unsignedInteger('qty');

            // Money
            $table->bigInteger('unit_cost'); // rupiah integer

            // Discount precision: basis points (0..10000 => 0.00%..100.00%)
            $table->unsignedInteger('disc_bps')->default(0);

            // Net line total AFTER discount, BEFORE header tax allocation
            $table->bigInteger('line_total')->default(0);

            $table->timestamps();

            $table->index(['purchase_invoice_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_invoice_lines');
    }
};
