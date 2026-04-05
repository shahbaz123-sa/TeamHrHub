<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Database\Factories\AttendanceFactory;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'latitude_in',
        'longitude_in',
        'latitude_out',
        'longitude_out',
        'check_in_address',
        'check_out_address',
        'status',
        'late_minutes',
        'early_check_in_minutes',
        'early_leaving_minutes',
        'overtime_minutes',
        'note',
        'created_by',
        'accuracy',
        'device',
        'check_in_from',
        'check_out_from',
        'check_in_other_location',
        'check_out_other_location',
        'check_in_time',
        'check_out_time',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in' => 'datetime:H:i:s',
        'check_out' => 'datetime:H:i:s',
        'check_in_time' => 'datetime:H:i:s',
        'check_out_time' => 'datetime:H:i:s',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function newFactory()
    {
        return AttendanceFactory::new();
    }

    //     we have "sdtatus" column in this table in that we are saving 
    // present,late and these string values
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'present':
                return 'Present';
            case 'late':
                return 'Late';
            case 'absent':
                return 'Absent';
            case "half_day":
                return "Half Day";
            default:
                return ucfirst($this->status);
        }
    }

}
