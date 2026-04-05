<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::create('notice_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->integer('order')->default(0)->after('is_active');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notice_types');
    }
};
