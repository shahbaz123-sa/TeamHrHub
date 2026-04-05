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
        Schema::table('products', function (Blueprint $table) {
            $table->index('name');
            $table->index('sku');
            $table->index('status');
            $table->index('stock_status');
            $table->index('price');
            $table->index('type');
            $table->index('parent_id');
        });

        Schema::table('product_category_map', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('product_id');
        });

        Schema::table('product_tag_map', function (Blueprint $table) {
            $table->index('tag_id');
            $table->index('product_id');
        });

        Schema::table('product_attribute_value_map', function (Blueprint $table) {
            $table->index('attribute_id');
            $table->index('attribute_value_id');
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex([
                'name',
                'sku',
                'status',
                'stock_status',
                'price',
                'type',
                'parent_id'
            ]);
        });

        Schema::table('product_category_map', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'product_id']);
        });

        Schema::table('product_tag_map', function (Blueprint $table) {
            $table->dropIndex(['tag_id', 'product_id']);
        });

        Schema::table('product_attribute_value_map', function (Blueprint $table) {
            $table->dropIndex(['attribute_id', 'attribute_value_id', 'product_id']);
        });
    }
};
