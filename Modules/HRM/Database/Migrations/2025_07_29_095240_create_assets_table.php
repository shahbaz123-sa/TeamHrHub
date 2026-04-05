<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->text('specifications')->nullable();
            $table->string('model')->nullable();
            $table->date('assigned_date');
            $table->date('return_date')->nullable();
            $table->timestamps();
        });
    }


    public function down()
    {
        Schema::dropIfExists('assets');
    }
};
