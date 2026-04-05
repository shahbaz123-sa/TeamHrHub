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
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('device_type')->nullable();
            $table->string('location')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('browser')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->unsignedBigInteger('personal_access_token_id')->nullable()->after('user_id');

            $table->foreign('personal_access_token_id')
                ->references('id')
                ->on('personal_access_tokens')
                ->nullOnDelete(); // If token deleted, keep activity but set null
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
