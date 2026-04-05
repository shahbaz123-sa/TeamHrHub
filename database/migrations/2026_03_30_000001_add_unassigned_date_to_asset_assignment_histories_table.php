<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_assignment_histories', function (Blueprint $table) {
            $table->date('unassigned_date')->nullable()->after('returned_at');
        });
    }

    public function down(): void
    {
        Schema::table('asset_assignment_histories', function (Blueprint $table) {
            $table->dropColumn('unassigned_date');
        });
    }
};

