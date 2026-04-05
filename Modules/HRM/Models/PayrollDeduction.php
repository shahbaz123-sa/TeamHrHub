<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class PayrollDeduction extends Model
{
    protected $table = 'payroll_deductions';
    protected $fillable = [
        'name',
        'description',
        'is_enabled',
    ];
}

