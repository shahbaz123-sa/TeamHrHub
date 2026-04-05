<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->integer('order')->default(0);
            $table->boolean('is_default')->default(false);
        });

        // Enforce: non-default must have order = 0
        DB::statement('
            ALTER TABLE document_types
            ADD CONSTRAINT chk_document_types_order
            CHECK (
                (is_default = true AND "order" > 0)
                OR
                (is_default = false AND "order" = 0)
            )
        ');

        // Enforce uniqueness ONLY when is_default = true
        DB::statement('
            CREATE UNIQUE INDEX uniq_document_types_default_order
            ON document_types ("order")
            WHERE is_default = true
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS uniq_document_types_default_order');
        DB::statement('ALTER TABLE document_types DROP CONSTRAINT IF EXISTS chk_document_types_order');

        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['order', 'is_default']);
        });
    }
};
