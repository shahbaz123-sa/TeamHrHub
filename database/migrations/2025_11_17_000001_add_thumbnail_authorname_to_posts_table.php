<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('thumbnail')->nullable()->after('header_image');
            $table->string('author_name')->nullable()->after('author_id');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('thumbnail');
            $table->dropColumn('author_name');
        });
    }
};
