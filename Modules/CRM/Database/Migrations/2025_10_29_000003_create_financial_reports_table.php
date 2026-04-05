<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'crm';

    public function up(): void
    {
        Schema::create('financial_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('press_release')->nullable();
            $table->string('financial_report')->nullable();
            $table->string('presentation')->nullable();
            $table->string('transcript')->nullable();
            $table->string('video')->nullable();
            $table->date('report_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_reports');
    }
};
