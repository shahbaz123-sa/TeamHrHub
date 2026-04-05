<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Customer\Company;

class Rfq extends Model
{
    protected $connection = 'crm';

    protected $table = 'rfqs';

    protected $fillable = [
        'company_id',
        'user_id',
        'assigned_to',
        'reference_no',
        'status',
        'delivery_location',
        'preferred_delivery_date',
        'supporting_documents',
        'unique_rfq_id',
        'payment_method',
    ];

    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function item()
    {
        return $this->hasOne(Rfq\Item::class, 'rfq_id');
    }

    public function quotation()
    {
        return $this->hasOne(Rfq\Quotation::class, 'rfq_id');
    }
}
