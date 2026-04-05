<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->integer('grace_period')->nullable()->after('email');
            $table->integer('attendance_radius')->nullable()->after('grace_period');
            $table->decimal('latitude', 10, 7)->nullable()->after('attendance_radius');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->time('office_start_time')->nullable()->after('longitude');
            $table->time('office_end_time')->nullable()->after('office_start_time');
        });
    }

    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn([
                'grace_period',
                'attendance_radius',
                'latitude',
                'longitude',
                'office_start_time',
                'office_end_time'
            ]);
        });
    }
};
