<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_images';

    protected $fillable = [
        'wc_id',
        'product_id',
        'src',
    ];

    public function getSrcAttribute($value)
    {
        return Storage::disk('s3')->url($value);
    }
}
