<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_attributes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_type_id');
            $table->string('name');
            $table->enum('field_type', ['string', 'number', 'date', 'boolean', 'select']);
            $table->json('options')->nullable(); // For dropdown/select values
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('asset_type_id')
                  ->references('id')
                  ->on('asset_types')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_attributes');
    }
};
