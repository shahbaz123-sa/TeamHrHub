<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetAssignmentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'employee_id',
        'assigned_date',
        'returned_at',
        'unassigned_date',
    ];

    protected $casts = [
        'assigned_date' => 'date',
        'returned_at' => 'datetime',
    ];

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

