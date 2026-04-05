<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\ExpenseTypeFactory::new();
    }
}
