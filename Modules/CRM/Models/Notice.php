<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $connection = 'crm';

    protected $fillable = [
        'title',
        'year',
        'type_id',
        'pdf_attachment',
        'excel_attachment',
        'is_active',
    ];

    public function type()
    {
        return $this->belongsTo(\Modules\CRM\Models\Notice\Type::class, 'type_id');
    }
}
