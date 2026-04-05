<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayslipType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\PayslipTypeFactory::new();
    }
}
