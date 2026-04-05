<?php

namespace Modules\CRM\Models\Customer;

use Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $connection = 'crm';

    protected $table = 'company';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'company_name',
        'company_image',
        'national_tax_number',
        'company_address',
        'incorporation',
        'industry_type',
        'status',
        'cnic_number',
        'company_type',
    ];

    public function documents()
    {
        return $this->hasMany(Company\Document::class);
    }

    public function contact()
    {
        return $this->hasOne(Company\Contact::class);
    }
}
