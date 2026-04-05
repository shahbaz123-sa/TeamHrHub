<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'grace_period',
        'attendance_radius',
        'latitude',
        'longitude',
        'office_start_time',
        'office_end_time',
        'allow_remote',
        'time_deviations',
    ];

    protected $casts = [
        'time_deviations' => 'array',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\BranchFactory::new();
    }
}
