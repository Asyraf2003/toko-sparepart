<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledgers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            // PURCHASE_IN | SALE_OUT | VOID_IN | ADJUSTMENT | RESERVE | RELEASE
            $table->string('type', 32)->index();

            // positive or negative delta (e.g. +5, -2)
            $table->integer('qty_delta');

            // references (transaction/purchase_invoice/adjustment/etc)
            $table->string('ref_type', 64)->nullable()->index();
            $table->unsignedBigInteger('ref_id')->nullable()->index();

            $table->unsignedBigInteger('actor_user_id')->nullable()->index();

            $table->timestamp('occurred_at')->index();

            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->index(['product_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledgers');
    }
};
