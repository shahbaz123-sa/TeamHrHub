<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            // Personal Details
            $table->string('name');
            $table->string('father_name')->nullable();
            $table->string('phone')->unique();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relation')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->date('dob')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('cnic')->unique()->nullable();
            $table->string('blood_group')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();
            $table->integer('dependents')->default(0);
            $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'temporary']);
            $table->enum('employment_status', ['active', 'on_leave', 'terminated', 'suspended']);
            $table->string('personal_email')->unique()->nullable();
            $table->string('official_email')->unique()->nullable();
            $table->string('official_email_password')->nullable();
            $table->boolean('status')->default(true);
            $table->text('address1')->nullable();
            $table->text('address2')->nullable();

            // Document paths
            $table->string('resume')->nullable();
            $table->string('experience_letter')->nullable();
            $table->string('salary_slip')->nullable();
            $table->string('photo')->nullable();
            $table->string('cnic_document')->nullable();
            $table->string('offer_letter')->nullable();
            $table->string('contract')->nullable();

            // Company Details
            $table->string('employee_code')->unique();
            $table->foreignId('branch_id')->nullable()->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->foreignId('designation_id')->nullable()->constrained();
            $table->date('date_of_joining')->nullable();
            $table->foreignId('reporting_to')->nullable()->constrained('employees', 'id')->onDelete('set null');
            $table->decimal('bonus', 10, 2)->nullable();

            // Additional HRM Module Relationships
            $table->foreignId('user_id')->nullable()->constrained()->after('id');
            $table->foreignId('termination_type_id')->nullable()->constrained('termination_types');
            $table->foreignId('job_category_id')->nullable()->constrained('job_categories');
            $table->foreignId('job_stage_id')->nullable()->constrained('job_stages');

            // Device Details
            $table->string('machine_name')->nullable();
            $table->string('system_processor')->nullable();
            $table->string('system_type')->nullable();
            $table->string('machine_password')->nullable();
            $table->integer('installed_ram')->nullable();
            $table->enum('headphone', ['Yes', 'No'])->nullable();
            $table->enum('mouse', ['Yes', 'No'])->nullable();
            $table->enum('laptop_charger', ['Yes', 'No'])->nullable();

            // Bank Details
            $table->string('account_holder_name')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_number')->nullable();
            $table->string('iban')->nullable();
            $table->string('branch_location')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('employees');
    }
};
