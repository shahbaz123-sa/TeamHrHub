<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollGenerationItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'payroll_generation_id',
        'employee_id',
        'month',
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'total_loans',
        'tax_amount',
        'attendance_deduction',
        'net_salary',
        'hr_approved',
        'hr_approved_at',
        'hr_approved_by',
        'ceo_approved',
        'ceo_approved_at',
        'ceo_approved_by',
        'status',
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_loans' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'attendance_deduction' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'hr_approved' => 'boolean',
        'ceo_approved' => 'boolean',
        'hr_approved_at' => 'datetime',
        'ceo_approved_at' => 'datetime',
    ];

    public function generation(): BelongsTo
    {
        return $this->belongsTo(PayrollGeneration::class, 'payroll_generation_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
