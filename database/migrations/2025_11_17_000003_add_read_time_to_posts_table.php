<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->string('read_time')->nullable()->after('author_designation');
        });
    }

    public function down()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn('read_time');
        });
    }
};
