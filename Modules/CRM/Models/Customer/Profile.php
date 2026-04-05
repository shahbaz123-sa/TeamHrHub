<?php

namespace Modules\CRM\Models\Customer;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $connection = 'crm';

    protected $table = 'user_profile';

    protected $fillable = [
        'user_id',
        'profile_image',
        'first_name',
        'last_name',
        'nationality',
        'date_of_birth',
    ];
}
