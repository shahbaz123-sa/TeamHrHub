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
        Schema::table('leaves', function (Blueprint $table) {
            // Add duration_type column with default value of 1 (full_day)
            // 1 = full_day, 2 = half_day, 3 = short_leave
            $table->tinyInteger('duration_type')->default(1)->after('leave_reason');
            
            // Add hours column - only used for short_leave
            $table->integer('hours')->nullable()->after('duration_type');
            
            // Add days column - store total days (e.g. 1, 0.5, 0.25)
            $table->decimal('days')->nullable()->after('hours');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leaves', function (Blueprint $table) {
            $table->dropColumn(['duration_type', 'hours', 'days']);
        });
    }
};
