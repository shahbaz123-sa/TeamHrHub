<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Department extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\DepartmentFactory::new();
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

}
