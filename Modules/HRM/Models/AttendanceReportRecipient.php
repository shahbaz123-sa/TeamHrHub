<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceReportRecipient extends Model
{
    protected $fillable = [
        'employee_id',
        'yesterday',
        'daily',
        'weekly',
        'monthly',
        'annual',
        'is_active',
    ];

    protected $casts = [
        'yesterday' => 'boolean',
        'daily'     => 'boolean',
        'weekly'    => 'boolean',
        'monthly'   => 'boolean',
        'annual'    => 'boolean',
        'is_active' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
