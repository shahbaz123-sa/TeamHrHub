<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeExemption extends Model
{
    use HasFactory;

    protected $table = 'employee_exemptions';

    protected $fillable = [
        'employee_id',
        'attendance_exemption',
    ];

    /**
     * Relationship to Employee
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
