<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTicketsTable extends Migration
{
    public function up()
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->foreignId('employee_id')->constrained('employees');
            $table->foreignId('department_id')->constrained('departments');
            $table->foreignId('poc_id')->constrained('employees');
            $table->foreignId('category_id')->constrained('ticket_categories');
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->enum('status', ['Open', 'Pending', 'Resolved', 'Closed'])->default('Open');
            $table->date('start_date');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tickets');
    }
}
