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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->integer('wc_id')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('products')->cascadeOnDelete();
            $table->string('slug')->nullable()->unique();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('type');
            $table->string('status');
            $table->string('stock_status');
            $table->decimal('price')->default(0);
            $table->decimal('regular_price')->default(0);
            $table->decimal('sale_price')->default(0);
            $table->integer('stock_quantity')->default(0);
            $table->decimal('weight')->default(0);
            $table->boolean('has_options')->default(false);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->timestamps();
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->integer('wc_id')->default(0);
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('src');
            $table->timestamps();
        });

        // PIVOT TABLES
        Schema::create('product_category_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('product_tag_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('product_tags')->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('product_attribute_value_map', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained('product_attributes')->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->constrained('product_attribute_values')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PIVOT TABLES
        Schema::dropIfExists('product_category_map');
        Schema::dropIfExists('product_tag_map');
        Schema::dropIfExists('product_attribute_value_map');

        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
