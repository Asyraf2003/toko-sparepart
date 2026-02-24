<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_payment_proof_submissions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_invoice_id')->index();
            $table->unsignedBigInteger('submitted_by_user_id')->index();
            $table->string('telegram_chat_id', 64)->index();

            $table->string('telegram_file_id', 255)->nullable();
            $table->string('telegram_message_id', 64)->nullable();

            $table->string('stored_path', 255); // storage/app/private/...
            $table->string('original_filename', 255)->nullable();

            $table->string('status', 16)->default('PENDING')->index(); // PENDING|APPROVED|REJECTED
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable()->index();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->index(['purchase_invoice_id', 'status'], 'tppps_pi_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_payment_proof_submissions');
    }
};
