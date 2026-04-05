<?php

namespace Modules\CRM\Models\CreditApplication;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Customer\Company;
use Modules\CRM\Models\CreditApplication;

class Document extends Model
{
    protected $connection = 'crm';

    protected $table = 'credit_application_documents';

    protected $fillable = [
        'credit_application_id',
        'document_type',
        'document_url',
        'file_name',
        'file_size',
        'mime_type',
        'company_id',
    ];

    public function creditApplication()
    {
        return $this->belongsTo(CreditApplication::class, 'credit_application_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }
}
