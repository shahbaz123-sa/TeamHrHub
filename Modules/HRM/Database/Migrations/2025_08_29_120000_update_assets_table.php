<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('asset_type_id')->nullable()->constrained('asset_types');
            $table->string('serial_no')->nullable()->after('name');
            $table->integer('amount')->nullable()->after('serial_no');
            $table->date('purchase_date')->nullable()->after('amount');
            $table->date('support_until')->nullable()->after('purchase_date');
            $table->longText('description')->nullable()->after('support_until');
            $table->foreignId('assign_to')->nullable()->constrained('employees');
            $table->dropColumn(['brand', 'model', 'specifications', 'assigned_date', 'return_date']);
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['asset_type_id', 'serial_no', 'amount', 'purchase_date', 'support_until', 'description']);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('specifications')->nullable();
            $table->date('assigned_date')->nullable();
            $table->date('return_date')->nullable();
        });
    }
};
