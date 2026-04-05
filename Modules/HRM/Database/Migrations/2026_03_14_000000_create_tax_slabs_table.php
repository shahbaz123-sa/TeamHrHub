<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->decimal('min_salary', 15, 2)->default(0);
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->decimal('tax_rate', 5, 2)->default(0); // percentage (0-100)
            $table->decimal('fixed_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index(['min_salary', 'max_salary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_slabs');
    }
};

