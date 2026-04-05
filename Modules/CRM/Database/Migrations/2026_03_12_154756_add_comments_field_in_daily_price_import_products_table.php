<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'crm';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('daily_price_import_products', function (Blueprint $table) {
            $table->text('comments')->nullable()->after('zarea_price_on_bulk');
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('city_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_price_import_products', function (Blueprint $table) {
            $table->dropColumn('comments');
        });
    }
};
