<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use Notifiable;

    protected $connection = 'crm';

    protected $table = 'user';

    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'type',
        'is_verified',
        'is_privacy',
        'created_at',
        'updated_at',
        'status',
        'is_temp_valid',
    ];

    public function profile()
    {
        return $this->hasOne(Customer\Profile::class, 'user_id');
    }

    public function company()
    {
        return $this->hasOne(Customer\Company::class, 'user_id');
    }

    public function shippingAddresses()
    {
        return $this->hasMany(Customer\ShippingAddress::class, 'user_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'user_id');
    }
}
