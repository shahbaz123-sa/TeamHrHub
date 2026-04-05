<?php

namespace Modules\CRM\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class ShippingAddress extends Model
{
    protected $connection = 'crm';

    protected $table = 'user_shipping_address';

    protected $fillable = [
        'user_id',
        'email',
        'full_name',
        'phone_number',
        'state',
        'city',
        'postcode',
        'street_address',
    ];
}
