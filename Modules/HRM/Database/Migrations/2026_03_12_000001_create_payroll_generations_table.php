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
        Schema::create('payroll_generations', function (Blueprint $table) {
            $table->id();
            $table->string('month', 7); // YYYY-MM
            $table->string('scope', 20)->default('company'); // company|department|employees
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('generated_by')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->index(['month']);
            $table->index(['department_id']);

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('generated_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_generations');
    }
};

