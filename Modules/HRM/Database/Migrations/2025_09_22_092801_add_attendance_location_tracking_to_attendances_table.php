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
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('early_check_in_minutes')->default(0)->after('late_minutes');
            $table->tinyInteger('check_in_from')->default(0)->after('early_check_in_minutes');
            $table->tinyInteger('check_out_from')->default(0)->after('check_in_from');
            $table->text('check_in_other_location')->nullable()->after('check_out_from');
            $table->text('check_out_other_location')->nullable()->after('check_in_other_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn([
                'early_check_in_minutes',
                'check_in_from',
                'check_out_from',
                'check_in_other_location',
                'check_out_other_location'
            ]);
        });
    }
};
