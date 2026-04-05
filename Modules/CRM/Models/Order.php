<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $connection = 'crm';

    protected $table = 'order';

    const CREATED_AT = 'createdAt';
    const UPDATED_AT = 'updatedAt';

    protected $fillable = [
        'user_id',
        'status',
        'payment_method',
        'payment_status',
        'subtotal',
        'discount',
        'shipping_fee',
        'total_amount',
        'state',
        'city',
        'postcode',
        'street_address',
        'shipping_option',
        'createdAt',
        'updatedAt',
        'platfoam',
        'unique_order_id',
        'order_type',
        'company_id',
        'rfq_id',
        'quotation_id',
        'preferred_delivery_date',
        'billing_address',
        'site_contact_person',
        'site_contact_phone',
        'special_instructions',
        'payment_schedule',
        'purchase_order_number',
        'finance_contact',
        'cancel_reason',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Customer\Company::class, 'company_id');
    }

    public function rfq()
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }

    public function items()
    {
        return $this->hasMany(Order\Item::class, 'order_id');
    }

    public function histories()
    {
        return $this->hasMany(Order\History::class, 'order_id');
    }

    public function documents()
    {
        return $this->hasMany(Order\Document::class, 'order_id');
    }
}
