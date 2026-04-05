<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'is_recurring',
        'description',
    ];

    protected $casts = [
        'date' => 'date',
        'is_recurring' => 'boolean',
    ];
}

