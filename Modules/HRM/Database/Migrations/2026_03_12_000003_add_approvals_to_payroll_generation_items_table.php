<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_generation_items', function (Blueprint $table) {
            $table->boolean('hr_approved')->default(false)->after('net_salary');
            $table->timestamp('hr_approved_at')->nullable()->after('hr_approved');
            $table->unsignedBigInteger('hr_approved_by')->nullable()->after('hr_approved_at');

            $table->boolean('ceo_approved')->default(false)->after('hr_approved_by');
            $table->timestamp('ceo_approved_at')->nullable()->after('ceo_approved');
            $table->unsignedBigInteger('ceo_approved_by')->nullable()->after('ceo_approved_at');

            $table->string('status', 30)->default('generated')->after('ceo_approved_by');

            $table->index(['status']);

            $table->foreign('hr_approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('ceo_approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payroll_generation_items', function (Blueprint $table) {
            $table->dropForeign(['hr_approved_by']);
            $table->dropForeign(['ceo_approved_by']);

            $table->dropIndex(['status']);

            $table->dropColumn([
                'hr_approved',
                'hr_approved_at',
                'hr_approved_by',
                'ceo_approved',
                'ceo_approved_at',
                'ceo_approved_by',
                'status',
            ]);
        });
    }
};

