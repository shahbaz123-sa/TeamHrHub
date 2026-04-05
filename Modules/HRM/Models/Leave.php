<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Leave extends Model
{
    use HasFactory, SoftDeletes;

    // Duration type constants
    const DURATION_FULL_DAY = 1;
    const DURATION_HALF_DAY = 2;
    const DURATION_SHORT_LEAVE = 3;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'leave_reason',
        'duration_type',
        'hours',
        'days',
        'leave_attachment',
        'manager_status',
        'hr_status',
        'is_paid',
        'total_paid_days',
        'total_unpaid_days',
        'paid_start_date',
        'paid_end_date',
        'unpaid_start_date',
        'unpaid_end_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_paid' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'paid_start_date' => 'date',
        'paid_end_date' => 'date',
        'unpaid_start_date' => 'date',
        'unpaid_end_date' => 'date',
        'duration_type' => 'integer',
        'hours' => 'integer',
        'days' => 'decimal:2',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * Get the duration type name
     */
    public function getDurationTypeNameAttribute()
    {
        return match($this->duration_type) {
            self::DURATION_FULL_DAY => 'full_day',
            self::DURATION_HALF_DAY => 'half_day',
            self::DURATION_SHORT_LEAVE => 'short_leave',
            default => 'full_day',
        };
    }

    /**
     * Get all duration types
     */
    public static function getDurationTypes()
    {
        return [
            self::DURATION_FULL_DAY => 'Full Day',
            self::DURATION_HALF_DAY => 'Half Day',
            self::DURATION_SHORT_LEAVE => 'Short Leave',
        ];
    }

    /**
     * Get the number of leave days
     */
    public function getLeaveDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $startDate = \Carbon\Carbon::parse($this->start_date);
        $endDate = \Carbon\Carbon::parse($this->end_date);
        
        // Calculate difference in days (inclusive of both start and end dates)
        return $startDate->diffInDays($endDate) + 1;
    }
}
