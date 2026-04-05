<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Deduction extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\DeductionFactory::new();
    }
}
