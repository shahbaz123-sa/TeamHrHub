<?php

namespace Modules\HRM\Models;

use Modules\HRM\Models\Employee;
use Illuminate\Database\Eloquent\Model;
use Modules\HRM\Database\Factories\DependentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Dependent extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'name',
        'relation',
        'phone',
        'date_of_birth',
        'age'
    ];

    protected $casts = [
        'date_of_birth' => 'date'
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    protected static function newFactory()
    {
        return DependentFactory::new();
    }
}
