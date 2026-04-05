<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_training_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_type_id')->constrained()->cascadeOnDelete();
            $table->date('completed_at')->nullable(); // Optional: Track training completion
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_training_type');
    }
};
