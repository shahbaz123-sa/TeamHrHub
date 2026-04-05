<?php

namespace Modules\CRM\Models\Order;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $connection = 'crm';

    protected $table = 'order_item';

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'price',
        'subtotal',
        'createdAt',
        'updatedAt',
        'city_id',
        'variation_id',
        'uom_name',
    ];

    public function order()
    {
        return $this->belongsTo(\Modules\CRM\Models\Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(\Modules\CRM\Models\Product::class, 'product_id')->whereIn('type', ['simple', 'variable']);
    }

    public function variation()
    {
        return $this->belongsTo(\Modules\CRM\Models\Product::class, 'variation_id')->where('type', 'variation');
    }
}
