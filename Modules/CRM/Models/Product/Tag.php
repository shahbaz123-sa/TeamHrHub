<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_tags';

    protected $fillable = [
        'wc_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];
}
