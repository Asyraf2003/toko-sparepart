<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->date('loan_date')->index();

            $table->bigInteger('amount');
            $table->bigInteger('outstanding_amount')->index();

            $table->string('note', 255)->nullable();

            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->index(['employee_id', 'loan_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_loans');
    }
};
