<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_states', function (Blueprint $table) {
            $table->id();
            $table->string('key', 190)->unique();
            $table->timestamp('sent_at');
            $table->text('meta_json')->nullable();
            $table->timestamps();

            $table->index(['sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_states');
    }
};
