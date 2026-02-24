<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('chat_id', 64)->unique();
            $table->string('state', 64); // e.g. AWAIT_INVOICE_NO, AWAIT_PROOF_UPLOAD
            $table->text('data_json')->nullable();
            $table->timestamps();

            $table->index(['state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_conversations');
    }
};