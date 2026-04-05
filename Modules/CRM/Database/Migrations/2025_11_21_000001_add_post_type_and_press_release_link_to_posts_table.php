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
            $table->enum('post_type', ['news', 'press_release'])->default('news')->after('status');
            $table->string('press_release_link')->nullable()->after('post_type');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('post_type');
            $table->dropColumn('press_release_link');
        });
    }
};
