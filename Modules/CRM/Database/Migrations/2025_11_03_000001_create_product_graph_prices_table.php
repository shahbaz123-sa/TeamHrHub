<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::create('product_graph_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->onDelete('set null');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('brand_id')->nullable()->constrained('product_brands')->onDelete('set null');
            $table->foreignId('unit_id')->nullable()->constrained('product_uoms')->onDelete('set null');
            $table->decimal('price');
            $table->timestamp('datetime');
            $table->string('market');
            $table->string('currency');
            $table->integer('uploaded_by')->nullable();
            $table->timestamps();
        });

        Schema::table('temp_wp_prices_sheet', function (Blueprint $table) {
            $table->integer('crm_brand_id')->nullable();
            $table->integer('crm_unit_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_graph_prices');

        Schema::table('temp_wp_prices_sheet', function (Blueprint $table) {
            $table->dropColumn('crm_brand_id');
            $table->dropColumn('crm_unit_id');
        });
    }
};
