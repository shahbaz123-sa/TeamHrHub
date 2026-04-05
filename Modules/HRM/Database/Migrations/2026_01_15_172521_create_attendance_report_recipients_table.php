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
        Schema::create('attendance_report_recipients', function (Blueprint $table) {
            $table->id()->key();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->boolean('yesterday')->nullable()->default(false);
            $table->boolean('daily')->nullable()->default(false);
            $table->boolean('weekly')->nullable()->default(false);
            $table->boolean('monthly')->nullable()->default(false);
            $table->boolean('annual')->nullable()->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreign('employee_id')
                ->references('id')
                ->on('employees')
                ->nullOnDelete();

            // prevent duplicate recipients
            $table->unique(['employee_id'], 'uniq_report_recipient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_report_recipients');
    }
};
