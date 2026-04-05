<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class TaxSlab extends Model
{
    protected $table = 'tax_slabs';

     protected $fillable = [
         'name',
         'min_salary',
         'max_salary',
         'tax_rate',
         'fixed_amount',
         'exceeding_threshold',
     ];

     protected $casts = [
         'min_salary' => 'decimal:2',
         'max_salary' => 'decimal:2',
         'tax_rate' => 'decimal:2',
         'fixed_amount' => 'decimal:2',
         'exceeding_threshold' => 'decimal:2',
     ];

    public function salary()
    {
        return $this->belongsTo(EmployeeSalary::class);
    }

}

