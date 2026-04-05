<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->boolean('is_tax_applicable')->default(false)->after('status');
            $table->foreignId('tax_slab_id')->nullable()->after('is_tax_applicable')->constrained('tax_slabs')->nullOnDelete();
            $table->decimal('tax_amount', 12, 2)->default(0)->after('tax_slab_id');
        });
    }

    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            if (Schema::hasColumn('employee_salaries', 'is_tax_applicable')) {
                $table->dropColumn('is_tax_applicable');
            }
            if (Schema::hasColumn('employee_salaries', 'tax_amount')) {
                $table->dropColumn('tax_amount');
            }
            if (Schema::hasColumn('employee_salaries', 'tax_slab_id')) {
                $table->dropForeign(['tax_slab_id']);
                $table->dropColumn('tax_slab_id');
            }
        });
    }
};

