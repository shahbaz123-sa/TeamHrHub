<?php

namespace Modules\Auth\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    protected $fillable = [
        'name',
        'guard_name',
    ];

    public function module()
    {
        return $this->belongsTo(PermissionModule::class, 'module_id');
    }
}
