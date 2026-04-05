<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Designation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'description'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\DesignationFactory::new();
    }
}
