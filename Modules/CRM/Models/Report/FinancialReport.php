<?php

namespace Modules\CRM\Models\Report;

use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    protected $connection = 'crm';

    protected $fillable = [
        'title',
        'report_date',
        'press_release',
        'financial_report',
        'presentation',
        'transcript',
        'video',
        'is_active',
    ];
}
