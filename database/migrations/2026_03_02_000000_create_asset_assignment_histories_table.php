<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('asset_assignment_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();

            // This mirrors the payload/pivot semantics (date-only).
            $table->date('assigned_date')->nullable();
            $table->timestamp('returned_at')->nullable();

            $table->timestamps();

            $table->index(['asset_id', 'returned_at']);
            $table->index(['employee_id', 'returned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_assignment_histories');
    }
};

