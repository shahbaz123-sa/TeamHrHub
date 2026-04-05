<?php

namespace Modules\CRM\Models\Rfq;

use Modules\CRM\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Rfq;

class Item extends Model
{
    protected $connection = 'crm';

    protected $table = 'rfq_items';

    protected $fillable = [
        'rfq_id',
        'product_id',
        'variation_id',
        'quantity',
        'uom',
        'technical_specs',
        'product_name',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variation()
    {
        return $this->belongsTo(Product::class, 'variation_id');
    }
}
