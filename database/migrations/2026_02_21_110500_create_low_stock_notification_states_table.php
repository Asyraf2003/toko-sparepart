<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('low_stock_notification_states', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->timestamp('last_notified_at')->nullable();
            $table->unsignedInteger('last_notified_available_qty')->nullable();

            $table->timestamps();

            $table->unique('product_id');
            $table->index(['last_notified_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('low_stock_notification_states');
    }
};
