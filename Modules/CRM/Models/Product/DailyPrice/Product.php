<?php

namespace Modules\CRM\Models\Product\DailyPrice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CRM\Models\Product as CrmProduct;
use Modules\CRM\Models\Product\City;

class Product extends Model
{
    protected $connection = 'crm';

    protected $table = 'daily_price_import_products';

    protected $fillable = [
        'batch_id',
        'product_id',
        'city_id',
        'product_sku',
        'category',
        'sub_category',
        'brand',
        'old_product_name',
        'new_product_name',
        'new_variant_name',
        'old_city',
        'city',
        'province',
        'new_city',
        'vendor_name',
        'vendor_price',
        'zarea_price',
        'old_delivered_price',
        'new_delivered_price',
        'min_order_qty',
        'price_bulk_qty',
        'zarea_price_on_bulk',
        'comments',
        'is_graph_product',
        'graph_category',
        'graph_product',
        'graph_product_unit',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class, 'batch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(CrmProduct::class, 'product_id');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class, 'city_id');
    }
}
