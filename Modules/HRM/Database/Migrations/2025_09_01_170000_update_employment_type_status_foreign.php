<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('employees', function (Blueprint $table) {

            $table->dropColumn(['employment_type', 'employment_status']);

            // Add new foreign keys
            $table->foreignId('employment_type_id')->nullable()->constrained('employment_types')->nullOnDelete();
            $table->foreignId('employment_status_id')->nullable()->constrained('employment_statuses')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['employment_type_id']);
            $table->dropColumn(['employment_status_id']);

            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary'])->default('full_time');
            $table->enum('employment_status', ['active', 'on_leave', 'terminated', 'suspended'])->default('active');
        });
    }
};
