<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_slabs', function (Blueprint $table) {
            $table->decimal('exceeding_threshold', 15, 2)->default(0)->after('fixed_amount');
        });
    }

    public function down(): void
    {
        Schema::table('tax_slabs', function (Blueprint $table) {
            $table->dropColumn('exceeding_threshold');
        });
    }
};

