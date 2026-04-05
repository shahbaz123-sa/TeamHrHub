<?php

namespace Modules\CRM\Models;

use Modules\CRM\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Modules\CRM\Models\Customer\Company;
use Modules\CRM\Models\Product\Category;

class CreditApplication extends Model
{
    protected $connection = 'crm';

    protected $table = 'credit_applications';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'credit_reference',
        'company_id',
        'requested_credit_limit',
        'category_id',
        'business_type',
        'annual_revenue',
        'purpose_of_credit',
        'status',
        'approved_credit_limit',
        'used_credit_limit',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
        'processing_time',
        'createdAt',
        'updatedAt',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    public function documents()
    {
        return $this->hasMany(CreditApplication\Document::class, 'credit_application_id');
    }
}
