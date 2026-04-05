<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;

class EmployeeAsset extends Model
{
    protected $table = 'employee_asset';

    protected $fillable = [
        'employee_id',
        'asset_id',
        'assigned_date',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }
}
