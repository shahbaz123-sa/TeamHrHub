<?php

namespace Modules\CRM\Models\Order;

use Modules\CRM\Models\Order;
use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    protected $table = 'order_histories';

    protected $fillable = [
        'order_id',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
