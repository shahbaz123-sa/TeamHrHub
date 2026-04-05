<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employee_attendance_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('day'); // e.g., Monday, Tuesday, etc.
            $table->boolean('inside_office')->default(false);
            $table->boolean('outside_office')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_attendance_days');
    }
};
