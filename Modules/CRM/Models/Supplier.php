<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $connection = 'crm';

    protected $table = 'suppliers';

    protected $fillable = [
        'name',
        'type',
        'phone',
        'email',
        'address',
        'brand',
        'product_category',
        'incorporation_letter',
        'request_letterhead',
    ];
}
