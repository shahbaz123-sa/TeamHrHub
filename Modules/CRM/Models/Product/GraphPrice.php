<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class GraphPrice extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_graph_prices';

    protected $fillable = [
        'category_name',
        'product_name',
        'brand_name',
        'price',
        'price_raw',
        'datetime',
        'datetime_raw',
        'market',
        'currency',
        'unit_name',
        'uploaded_by',
    ];

    public function uploader()
    {
        return $this->setConnection('pgsql')->belongsTo(\Modules\Auth\Models\User::class, 'uploaded_by');
    }
}
