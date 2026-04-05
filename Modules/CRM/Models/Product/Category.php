<?php

namespace Modules\CRM\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $connection = 'crm';

    protected $table = 'product_categories';

    protected $fillable = [
        'wc_id',
        'name',
        'slug',
        'description',
        'parent_id',
        'image',
        'is_active',
    ];

    public function parent()
    {
        return $this->hasOne(Category::class, 'id', 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id', 'id');
    }
}
