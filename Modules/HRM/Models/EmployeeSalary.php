<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeSalary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'payslip_type_id',
        'is_tax_applicable',
        'tax_slab_id',
        'tax_amount',
        'amount',
        'effective_date',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'effective_date' => 'date',
        'status' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function payslipType()
    {
        return $this->belongsTo(PayslipType::class);
    }

    public function histories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    public function taxSlab()
    {
        return $this->belongsTo(TaxSlab::class, 'tax_slab_id');
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\EmployeeSalaryFactory::new();
    }
}
