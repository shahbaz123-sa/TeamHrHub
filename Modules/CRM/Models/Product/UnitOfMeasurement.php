<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class UnitOfMeasurement extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_uoms';

    protected $fillable = [
        'name',
        'slug',
        'is_active',
    ];
}
