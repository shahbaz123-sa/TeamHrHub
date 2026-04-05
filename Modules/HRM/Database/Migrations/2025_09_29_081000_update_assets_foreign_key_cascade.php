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
        Schema::table('assets', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['asset_type_id']);
            
            // Recreate the foreign key constraint with CASCADE delete
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
        Schema::table('assets', function (Blueprint $table) {
            // Drop the cascade foreign key constraint
            $table->dropForeign(['asset_type_id']);
            
            // Recreate the original foreign key constraint with NO ACTION
            $table->foreign('asset_type_id')
                  ->references('id')
                  ->on('asset_types')
                  ->onDelete('no action');
        });
    }
};

