<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    protected $connection = 'crm';

    protected $table = 'email_settings';

    protected $fillable = [
        'notify_on',
        'slug',
        'recipients',
        'is_active',
    ];
}
