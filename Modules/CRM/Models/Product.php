<?php

namespace Modules\CRM\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $connection = 'crm';

    protected $fillable = [
        'wc_id',
        'parent_id',
        'wc_slug',
        'wc_sku',
        'sku',
        'name',
        'type',
        'status',
        'stock_status',
        'ask_for_quote',
        'price',
        'regular_price',
        'sale_price',
        'quantity',
        'min_quantity',
        'max_quantity',
        'short_description',
        'description',
        'uom_id',
        'brand_id',
    ];

    public function scopeActiveOnly($query)
    {
        $query->where('status', '=', 'publish');
    }

    public function scopeOnlyVariations($query)
    {
        $query->where('type', '=', 'variation');
    }

    public function scopeVariationsOf($query, int $parentId)
    {
        $query->where('type', '=', 'variation')->where('parent_id', '=', $parentId);
    }

    public function uom()
    {
        return $this->belongsTo(Product\UnitOfMeasurement::class, 'uom_id');
    }

    public function brand()
    {
        return $this->belongsTo(Product\Brand::class, 'brand_id');
    }

    public function prices()
    {
        return $this->hasMany(Product\CityWisePrice::class);
    }

    public function images()
    {
        return $this->hasMany(Product\Image::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Product\Tag::class, 'product_tag_map')
            ->withTimestamps();
    }

    public function categories()
    {
        return $this->belongsToMany(Product\Category::class, 'product_category_map')
            ->withTimestamps();
    }

    public function parent()
    {
        return $this->hasOne(Product::class, 'id', 'parent_id');
    }

    public function variations()
    {
        return $this->hasMany(Product::class, 'parent_id', 'id')->where('type', 'variation');
    }

    public function attributes()
    {
        return $this->belongsToMany(
            Product\Attribute::class,
            'product_attribute_value_map',
            'product_id',
            'attribute_id'
        )->withTimestamps();
    }

    public function attributeValues()
    {
        return $this->belongsToMany(
            Product\Attribute\Value::class,
            'product_attribute_value_map',
            'product_id',
            'attribute_value_id'
        )->withTimestamps();
    }
}
