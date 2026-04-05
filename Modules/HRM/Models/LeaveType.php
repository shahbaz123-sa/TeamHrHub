<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeaveType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description', 'quota', 'sort_order'];

    protected $casts = [
        'quota' => 'integer',
        'sort_order' => 'integer',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\LeaveTypeFactory::new();
    }
}
