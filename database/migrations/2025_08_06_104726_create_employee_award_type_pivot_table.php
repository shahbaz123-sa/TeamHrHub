<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_award_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('award_type_id')->constrained()->cascadeOnDelete();
            $table->date('awarded_at')->nullable(); // When the award was given
            $table->text('notes')->nullable(); // Optional: Reason for award
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_award_type');
    }
};
