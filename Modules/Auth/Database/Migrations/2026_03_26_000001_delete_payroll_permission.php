<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Delete all permissions with name like 'payroll.%' (all actions)
        DB::table('permissions')->where('name', 'like', 'payroll.%')->delete();
        DB::table('permissions')->where('name', 'like', 'salary.%')->delete();
    }

    public function down(): void
    {
        // No-op: cannot restore deleted permissions without knowing original module_id, etc.
    }
};

