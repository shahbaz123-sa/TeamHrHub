<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\CRM\Models\AssignedManager;
use Modules\HRM\Repositories\EmployeeRepository;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('crm')->create('assigned_managers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('name')->nullable();
            $table->string('profileImage')->nullable();
            $table->string('role')->nullable();
            $table->string('employment_status')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        $managers = app(EmployeeRepository::class)->getRfqManagers([]);

        foreach ($managers as $manager) {
            AssignedManager::updateOrCreate([
                'employee_id' => $manager->id,
            ], [
                'name' => $manager->name,
                'profileImage' => $manager->photo,
                'role' => $manager->designation->title,
                'employment_status' => $manager->employmentStatus->name ?? null,
                'status' => $manager->status,
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('crm')->dropIfExists('assigned_managers');
    }
};
