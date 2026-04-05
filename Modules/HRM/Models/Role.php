<?php

namespace Modules\HRM\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    public function assignableRoles()
    {
        return $this->belongsToMany(Role::class, 'role_assignable_roles', 'role_id', 'assignable_role_id');
    }
}
