<?php

namespace Modules\CRM\Models\Product\DailyPrice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Auth\Models\User;

class Batch extends Model
{
    protected $connection = 'crm';

    protected $table = 'daily_price_import_batches';

    protected $fillable = [
        'status',
        'price_date',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'created_by',
    ];

    public function approver()
    {
        return $this->setConnection('pgsql')->belongsTo(User::class, 'approved_by');
    }

    public function rejecter()
    {
        return $this->setConnection('pgsql')->belongsTo(User::class, 'rejected_by');
    }

    public function creater()
    {
        return $this->setConnection('pgsql')->belongsTo(User::class, 'created_by');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'batch_id');
    }
}
