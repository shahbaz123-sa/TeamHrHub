<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\CRM\Models\Product;

class CityWisePrice extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_city_wise_prices';

    protected $fillable = [
        'product_id',
        'city_id',
        'price',
    ];

    /**
     * Get the product that owns the CityWisePrice
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
