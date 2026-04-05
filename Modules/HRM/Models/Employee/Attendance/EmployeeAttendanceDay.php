<?php

namespace Modules\HRM\Models\Employee\Attendance;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Models\Employee;

class EmployeeAttendanceDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'day',
        'is_working_day',
        'inside_office',
        'outside_office',
        'checkin_time',
        'checkout_time',
        'allow_late_checkin',
        'checkin_time',
        'checkout_time',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
