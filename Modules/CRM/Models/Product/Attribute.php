<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_attributes';

    protected $fillable = [
        'wc_id',
        'name',
        'slug',
        'is_active',
    ];

    public function values()
    {
        return $this->hasMany(Attribute\Value::class, 'attribute_id');
    }
}
