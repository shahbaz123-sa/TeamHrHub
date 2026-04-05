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
            $table->string('is_graph_product')->nullable()->after('comments');
            $table->string('graph_category')->nullable()->after('is_graph_product');
            $table->string('graph_product')->nullable()->after('graph_category');
            $table->string('graph_product_unit')->nullable()->after('graph_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('daily_price_import_products', function (Blueprint $table) {
            $table->dropColumn([
                'is_graph_product',
                'graph_category',
                'graph_product',
                'graph_product_unit',
            ]);
        });
    }
};
