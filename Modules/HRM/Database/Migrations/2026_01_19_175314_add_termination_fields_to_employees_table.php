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
        Schema::table('employees', function (Blueprint $table) {
            $table->text('termination_reason')
                ->nullable()
                ->after('termination_type_id');

            $table->date('termination_date')
                ->nullable()
                ->after('termination_reason');

            $table->date('termination_effective_date')
                ->nullable()
                ->after('termination_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'termination_reason',
                'termination_date',
                'termination_effective_date',
            ]);
        });
    }
};
