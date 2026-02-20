<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku', 64)->unique();
            $table->string('name', 190);

            $table->bigInteger('sell_price_current');
            $table->unsignedInteger('min_stock_threshold')->default(3);

            $table->boolean('is_active')->default(true)->index();

            // Moving average cost (COGS)
            $table->bigInteger('avg_cost')->default(0);

            $table->timestamps();

            $table->index(['name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
