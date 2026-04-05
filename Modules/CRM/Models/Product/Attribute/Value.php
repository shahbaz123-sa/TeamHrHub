<?php

namespace Modules\CRM\Models\Product\Attribute;

use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Product\Attribute;

class Value extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_attribute_values';

    protected $fillable = [
        'wc_id',
        'attribute_id',
        'name',
        'slug',
        'description',
        'is_active',
    ];

    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }
}
