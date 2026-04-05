<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class FormSubmission extends Model
{
    protected $connection = 'crm';

    protected $table = 'form_submissions';

    protected $fillable = [
        'form_type',
        'user_id',
        'full_name',
        'email',
        'phone',
        'city',
        'location',
        'company_name',
        'company_type',
        'commodity',
        'product_required',
        'quantity_required',
        'payment_method',
        'credit_term',
        'preferred_date',
        'delivery_location',
        'interested_in',
        'technical_specs',
        'message',
        'privacy_ask',
        'user_type',
        'industry_type',
        'uom_name',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_required');
    }

    public function category()
    {
        return $this->belongsTo(Product\Category::class, 'commodity');
    }
}
