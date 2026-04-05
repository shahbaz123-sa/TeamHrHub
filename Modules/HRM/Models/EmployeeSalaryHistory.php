<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeSalaryHistory extends Model
{
    protected $fillable = [
        'employee_id',
        'employee_salary_id',
        'action',
        'amount',
        'tax_amount',
        'is_tax_applicable',
        'tax_slab_id',
        'effective_date',
        'status',
        'payload',
        'performed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'is_tax_applicable' => 'boolean',
        'effective_date' => 'date',
        'payload' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salary()
    {
        return $this->belongsTo(EmployeeSalary::class, 'employee_salary_id');
    }

    public function taxSlab()
    {
        return $this->belongsTo(TaxSlab::class);
    }

    public function performedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }
}

