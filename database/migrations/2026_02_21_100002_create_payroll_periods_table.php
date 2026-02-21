<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_periods', function (Blueprint $table) {
            $table->id();

            $table->date('week_start')->index(); // must be Monday
            $table->date('week_end')->index();   // must be Saturday

            $table->string('note', 255)->nullable();

            // idempotency guard for ApplyLoanDeduction
            $table->timestamp('loan_deductions_applied_at')->nullable()->index();

            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['week_start', 'week_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_periods');
    }
};
