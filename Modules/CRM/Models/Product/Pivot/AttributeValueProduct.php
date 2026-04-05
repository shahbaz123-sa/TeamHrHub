<?php

namespace Modules\CRM\Models\Product\Pivot;

use Illuminate\Database\Eloquent\Model;

class AttributeValueProduct extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_attribute_value_map';

    protected $fillable = [
        'product_id',
        'attribute_id',
        'attribute_value_id',
    ];
}
