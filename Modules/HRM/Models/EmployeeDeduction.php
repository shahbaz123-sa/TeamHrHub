<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeDeduction extends Model
{
    use HasFactory;

    protected $table = 'employee_deduction';

    protected $fillable = [
        'employee_id',
        'deduction_id',
        'title',
        'type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class);
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\EmployeeDeductionFactory::new();
    }
}
