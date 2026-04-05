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
        Schema::create('temp_wp_prices_sheet', function (Blueprint $table) {
            $table->id();
            $table->integer('category_id')->nullable();
            $table->integer('crm_category_id')->nullable();
            $table->integer('product_id')->nullable();
            $table->integer('crm_product_id')->nullable();
            $table->string('product_name')->nullable();
            $table->string('crm_product_name')->nullable();
            $table->string('brand')->nullable();
            $table->string('datetime')->nullable();
            $table->string('market')->nullable();
            $table->string('currency')->nullable();
            $table->decimal('price')->nullable();
            $table->string('unit')->nullable();
            $table->string('uploaded_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('temp_wp_prices_sheet');
    }
};
