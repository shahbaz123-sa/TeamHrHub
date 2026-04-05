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
        Schema::table('employee_attendance_days', function (Blueprint $table) {
            $table->time('checkin_time')->nullable();
            $table->time('checkout_time')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_attendance_days', function (Blueprint $table) {
            $table->dropColumn([
            'checkin_time',
            'checkout_time',
            ]);
        });
    }
};
