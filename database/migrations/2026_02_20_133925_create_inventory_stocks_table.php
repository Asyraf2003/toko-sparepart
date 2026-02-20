<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_stocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedInteger('on_hand_qty')->default(0);
            $table->unsignedInteger('reserved_qty')->default(0);

            $table->timestamps();

            $table->unique('product_id');
            $table->index(['on_hand_qty', 'reserved_qty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_stocks');
    }
};
