<?php

namespace Modules\CRM\Models\Rfq;

use Illuminate\Database\Eloquent\Model;

class Quotation extends Model
{
    protected $connection = 'crm';

    protected $table = 'rfq_quotations';

    protected $fillable = [
        'rfq_id',
        'procs',
        'due_date',
        'total_price',
        'price_per_unit',
        'invoice',
    ];

    public function rfq()
    {
        return $this->belongsTo(Rfq::class, 'rfq_id');
    }
}
