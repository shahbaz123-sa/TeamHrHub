<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::connection('crm')->table('posts', function (Blueprint $table) {
            $table->string('author_profile_image')->nullable()->after('author_name');
            $table->string('author_designation')->nullable()->after('author_profile_image');
        });
    }

    public function down()
    {
        Schema::connection('crm')->table('posts', function (Blueprint $table) {
            $table->dropColumn('author_profile_image');
            $table->dropColumn('author_designation');
        });
    }
};
