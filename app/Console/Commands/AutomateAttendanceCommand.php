<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\HRM\Models\Attendance;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class AutomateAttendanceCommand extends Command
{
    protected $signature = 'attendance:automate';

    protected $description = 'Automates attendance calculation every 2 hours';

    public function handle()
    {
        $today = now()->toDateString();
        Attendance::create([
            'employee_id' => 2,
            'date'        => $today,
            'status' => 'present',
            'late_minutes' => 0,
            'early_leaving_minutes' => 0,
            'overtime_minutes' => 0,
            'early_check_in_minutes' => 0,
            'check_in_from' => 0,
            'check_out_from' => 0,
            'created_by' => 2,
        ]);
    }
}
