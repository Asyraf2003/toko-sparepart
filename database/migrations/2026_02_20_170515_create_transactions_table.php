<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_number', 32)->unique();

            $table->date('business_date')->index();

            $table->string('status', 16)->index(); // DRAFT|OPEN|COMPLETED|VOID
            $table->string('payment_status', 16)->index(); // UNPAID|PAID
            $table->string('payment_method', 16)->nullable()->index(); // CASH|TRANSFER|null

            $table->string('rounding_mode', 32)->nullable(); // NEAREST_1000
            $table->integer('rounding_amount')->default(0);

            $table->integer('cash_received')->nullable();
            $table->integer('cash_change')->nullable();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('vehicle_plate')->nullable();

            $table->foreignId('service_employee_id')->nullable()->constrained('employees');
            $table->text('note')->nullable();

            $table->timestamp('opened_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('voided_at')->nullable();

            $table->foreignId('created_by_user_id')->constrained('users');

            $table->timestamps();

            $table->index(['created_by_user_id', 'business_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
