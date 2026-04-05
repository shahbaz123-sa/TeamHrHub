<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('employee_expense_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_type_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2)->nullable(); // Optional: Track expense amounts
            $table->date('expense_date')->nullable(); // Optional: When the expense occurred
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_expense_type');
    }
};
