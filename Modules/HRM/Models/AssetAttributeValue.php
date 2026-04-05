<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_id',
        'attribute_id',
        'value',
    ];

    /**
     * Get the asset that owns the attribute value.
     */
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the attribute that this value belongs to.
     */
    public function attribute()
    {
        return $this->belongsTo(AssetAttribute::class, 'attribute_id');
    }

    /**
     * Scope to filter by asset.
     */
    public function scopeForAsset($query, $assetId)
    {
        return $query->where('asset_id', $assetId);
    }

    /**
     * Scope to filter by attribute.
     */
    public function scopeForAttribute($query, $attributeId)
    {
        return $query->where('attribute_id', $attributeId);
    }
}

