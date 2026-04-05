<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class AssignedManager extends Model
{
    protected $connection = 'crm';

    protected $table = 'assigned_managers';

    protected $fillable = [
        'employee_id',
        'name',
        'profileImage',
        'role',
        'employment_status',
        'status',
    ];
}
