<?php

namespace Modules\CRM\Models\Report;

use Illuminate\Database\Eloquent\Model;

class LatestReport extends Model
{
    protected $connection = 'crm';

    protected $fillable = [
        'title',
        'attachment',
        'report_date',
        'is_active',
    ];
}
