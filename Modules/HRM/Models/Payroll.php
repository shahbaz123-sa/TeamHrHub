<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payroll extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'employee_id',
        'month',
        'basic_salary',
        'total_allowances',
        'total_deductions',
        'total_loans',
        'net_salary',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'basic_salary' => 'decimal:2',
        'total_allowances' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'total_loans' => 'decimal:2',
        'net_salary' => 'decimal:2',
    ];

    /**
     * Get the employee that owns the payroll.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Scope a query to only include payrolls for a specific month.
     */
    public function scopeForMonth($query, string $month)
    {
        return $query->where('month', $month);
    }

    /**
     * Scope a query to only include payrolls for a specific employee.
     */
    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Calculate net salary automatically.
     */
    public function calculateNetSalary(): float
    {
        return $this->basic_salary + $this->total_allowances - $this->total_deductions - $this->total_loans;
    }

    /**
     * Boot method to automatically calculate net salary.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($payroll) {
            $payroll->net_salary = $payroll->calculateNetSalary();
        });
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\PayrollFactory::new();
    }
}
