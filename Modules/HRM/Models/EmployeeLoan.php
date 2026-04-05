<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeLoan extends Model
{
    use HasFactory;

    protected $table = 'employee_loans';

    protected $fillable = [
        'employee_id',
        'loan_id',
        'title',
        'type',
        'reasons',
        'amount',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'type' => 'integer',
        'status' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function loan()
    {
        return $this->belongsTo(LoanOption::class, 'loan_id');
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\EmployeeLoanFactory::new();
    }
}
