<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            $table->bigInteger('gross_pay');
            $table->bigInteger('loan_deduction')->default(0);
            $table->bigInteger('net_paid');

            $table->string('note', 255)->nullable();

            $table->timestamps();

            $table->unique(['payroll_period_id', 'employee_id']);
            $table->index(['employee_id', 'payroll_period_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_lines');
    }
};
