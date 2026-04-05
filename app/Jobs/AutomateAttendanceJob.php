<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\HRM\Models\Attendance;

class AutomateAttendanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, \Illuminate\Bus\Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = now()->toDateString();
        Attendance::create([
            'employee_id' => 200,
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
