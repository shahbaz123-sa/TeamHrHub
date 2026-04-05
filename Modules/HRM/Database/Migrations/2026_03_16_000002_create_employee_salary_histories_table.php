<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_salary_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('employee_salary_id')->nullable()->constrained('employee_salaries')->nullOnDelete();
            $table->string('action', 20);
            $table->decimal('amount', 12, 2)->nullable();
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->boolean('is_tax_applicable')->default(false);
            $table->foreignId('tax_slab_id')->nullable()->constrained('tax_slabs')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->tinyInteger('status')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_salary_histories');
    }
};

