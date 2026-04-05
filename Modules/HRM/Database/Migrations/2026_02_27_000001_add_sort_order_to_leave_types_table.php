<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->nullable()->after('quota');
            $table->index('sort_order');
        });

        // Backfill for existing rows in a stable way.
        // Use created_at then id to keep legacy order predictable.
        DB::table('leave_types')
            ->orderBy('created_at')
            ->orderBy('id')
            ->select('id')
            ->chunkById(200, function ($rows) {
                static $i = 1;
                foreach ($rows as $row) {
                    DB::table('leave_types')
                        ->where('id', $row->id)
                        ->update(['sort_order' => $i++]);
                }
            });

        Schema::table('leave_types', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $table->dropIndex(['sort_order']);
            $table->dropColumn('sort_order');
        });
    }
};

