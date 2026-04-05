<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_cities';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];
}
