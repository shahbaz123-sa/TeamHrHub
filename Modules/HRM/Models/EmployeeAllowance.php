<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EmployeeAllowance extends Model
{
    use HasFactory;

    protected $table = 'employee_allowance';

    protected $fillable = [
        'employee_id',
        'allowance_id',
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

    public function allowance()
    {
        return $this->belongsTo(Allowance::class);
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\EmployeeAllowanceFactory::new();
    }
}
