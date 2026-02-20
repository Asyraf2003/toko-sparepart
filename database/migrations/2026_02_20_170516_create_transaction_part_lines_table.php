<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_part_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_id')->constrained('transactions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');

            $table->integer('qty');
            $table->integer('unit_sell_price_frozen');
            $table->integer('line_subtotal');

            $table->integer('unit_cogs_frozen')->nullable();

            $table->timestamps();

            $table->index(['transaction_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_part_lines');
    }
};
