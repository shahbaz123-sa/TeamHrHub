<?php

namespace Modules\CRM\Models\Customer\Company;

use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    protected $connection = 'crm';

    protected $table = 'company_contact';

    protected $fillable = [
        'company_id',
        'email',
        'phone_number',
        'name',
        'designation',
    ];
}
