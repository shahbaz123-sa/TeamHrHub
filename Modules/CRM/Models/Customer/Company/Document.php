<?php

namespace Modules\CRM\Models\Customer\Company;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $connection = 'crm';

    protected $table = 'company_documents';

    public $timestamps = false;

    protected $fillable = [
        'company_id',
        'document_type',
        'document_url',
    ];
}
