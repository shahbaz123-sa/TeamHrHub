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
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->index('name');
            $table->index('slug');
            $table->index('attribute_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attribute_values', function (Blueprint $table) {
            $table->dropIndex(['name', 'slug', 'attribute_id']);
        });
    }
};
