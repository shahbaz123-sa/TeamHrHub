<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_brands';

    protected $fillable = [
        'name',
        'slug',
        'image',
        'is_active',
    ];
}
