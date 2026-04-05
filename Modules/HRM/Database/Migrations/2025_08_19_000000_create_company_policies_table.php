<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('company_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamps();

            $table->index(['branch_id', 'display_order']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('company_policies');
    }
};
