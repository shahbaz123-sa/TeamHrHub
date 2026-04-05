<?php

namespace Modules\CRM\Models\Order;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Order;
use Modules\CRM\Models\Customer\Company;

class Document extends Model
{
    protected $connection = 'crm';

    protected $table = 'order_documents';

    protected $fillable = [
        'order_id',
        'company_id',
        'document_type',
        'document_url',
    ];
}
