<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::table('product_graph_prices', function (Blueprint $table) {
            $table->string('category_name')->nullable()->after('category_id');
            $table->string('product_name')->nullable()->after('product_id');
            $table->string('brand_name')->nullable()->after('brand_id');
            $table->string('unit_name')->nullable()->after('unit_id');
            $table->string('datetime_raw')->nullable()->after('datetime');
            $table->string('price_raw')->nullable()->after('price');

            $table->dropColumn(['category_id', 'product_id', 'brand_id', 'unit_id']);

            $table->index('category_name');
            $table->index('product_name');
            $table->index('brand_name');
            $table->index('unit_name');
        });
    }

    public function down(): void
    {
        Schema::table('product_graph_prices', function (Blueprint $table) {
            $table->dropColumn('category_name');
            $table->dropColumn('product_name');
            $table->dropColumn('brand_name');
            $table->dropColumn('unit_name');
            $table->dropColumn('datetime_raw');
            $table->dropColumn('price_raw');

            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->onDelete('set null');
            $table->foreignId('unit_id')->nullable()->constrained('product_uoms')->onDelete('set null');
        });
    }
};
