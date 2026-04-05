<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_allowance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allowance_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->nullable(); // Optional: If allowance has a custom amount per employee
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_allowance');
    }
};
