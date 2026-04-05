<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    protected $connection = 'crm';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_slug_unique');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->renameColumn('sku', 'wc_sku');
            $table->renameColumn('slug', 'wc_slug');
            $table->renameColumn('stock_quantity', 'quantity');

            $table->string('sku')->nullable();
            $table->integer('min_quantity')->default(0);
            $table->integer('max_quantity')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('sku');
            $table->dropColumn('min_quantity');
            $table->dropColumn('max_quantity');

            $table->renameColumn('wc_sku', 'sku');
            $table->renameColumn('wc_slug', 'slug');
            $table->renameColumn('quantity', 'stock_quantity');
        });
    }
};
