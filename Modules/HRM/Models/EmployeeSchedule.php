<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'start_time',
        'end_time',
        'working_days',
        'is_default',
        'effective_from',
        'effective_to'
    ];

    protected $casts = [
        'working_days' => 'array',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_default' => 'boolean'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
