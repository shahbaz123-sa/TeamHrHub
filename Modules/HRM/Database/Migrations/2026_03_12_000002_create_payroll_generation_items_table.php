<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payroll_generation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_generation_id');
            $table->unsignedBigInteger('employee_id');
            $table->string('month', 7); // duplicate for faster searching

            $table->decimal('basic_salary', 15, 2)->default(0);
            $table->decimal('total_allowances', 15, 2)->default(0);
            $table->decimal('total_deductions', 15, 2)->default(0);
            $table->decimal('total_loans', 15, 2)->default(0);
            $table->decimal('net_salary', 15, 2)->default(0);

            $table->timestamps();

            $table->unique(['employee_id', 'month']);
            $table->index(['month']);
            $table->index(['payroll_generation_id']);

            $table->foreign('payroll_generation_id')
                ->references('id')->on('payroll_generations')
                ->cascadeOnDelete();

            $table->foreign('employee_id')
                ->references('id')->on('employees')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_generation_items');
    }
};

