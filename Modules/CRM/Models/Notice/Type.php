<?php

namespace Modules\CRM\Models\Notice;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    protected $connection = 'crm';

    protected $table = 'notice_types';

    protected $fillable = [
        'title',
        'is_active',
        'order',
    ];
}
