<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('leave_reason')->nullable();
            $table->string('leave_attachment')->nullable();
            $table->enum('manager_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->enum('hr_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('is_paid')->default(false);
            $table->unsignedInteger('total_paid_days')->nullable();
            $table->unsignedInteger('total_unpaid_days')->nullable();
            $table->date('paid_start_date')->nullable();
            $table->date('paid_end_date')->nullable();
            $table->date('unpaid_start_date')->nullable();
            $table->date('unpaid_end_date')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
