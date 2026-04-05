<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    protected $connection = 'crm';

    public function up()
    {
        Schema::create('daily_price_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('status')->default('Pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('daily_price_import_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('daily_price_import_batches')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('city_id')->constrained('product_cities')->onDelete('cascade');
            $table->string('product_sku')->nullable();
            $table->string('category')->nullable();
            $table->string('sub_category')->nullable();
            $table->string('brand')->nullable();
            $table->string('old_product_name')->nullable();
            $table->string('new_product_name')->nullable();
            $table->string('new_variant_name')->nullable();
            $table->string('old_city')->nullable();
            $table->string('city')->nullable();
            $table->string('province')->nullable();
            $table->string('new_city')->nullable();
            $table->string('vendor_name')->nullable();
            $table->string('vendor_price')->nullable();
            $table->string('zarea_price')->nullable();
            $table->string('old_delivered_price')->nullable();
            $table->string('new_delivered_price')->nullable();
            $table->string('min_order_qty')->nullable();
            $table->string('price_bulk_qty')->nullable();
            $table->string('zarea_price_on_bulk')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_price_import_products');
        Schema::dropIfExists('daily_price_import_batches');
    }
};
