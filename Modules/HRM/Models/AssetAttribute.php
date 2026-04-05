<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AssetAttribute extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        'asset_type_id',
        'name',
        'field_type',
        'options',
    ];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Get the asset type that owns the attribute.
     */
    public function assetType()
    {
        return $this->belongsTo(AssetType::class);
    }

    /**
     * Get the attribute values for this attribute.
     */
    public function attributeValues()
    {
        return $this->hasMany(AssetAttributeValue::class, 'attribute_id');
    }

    /**
     * Get the assets that have this attribute.
     */
    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'asset_attribute_values', 'attribute_id', 'asset_id');
    }

    /**
     * Scope to filter by asset type.
     */
    public function scopeForAssetType($query, $assetTypeId)
    {
        return $query->where('asset_type_id', $assetTypeId);
    }

    /**
     * Scope to filter by field type.
     */
    public function scopeByFieldType($query, $fieldType)
    {
        return $query->where('field_type', $fieldType);
    }

    /**
     * Get formatted options for display.
     */
    public function getFormattedOptionsAttribute()
    {
        if (!$this->options || !is_array($this->options)) {
            return null;
        }
        
        return implode(', ', $this->options);
    }

    /**
     * Check if this attribute has options.
     */
    public function hasOptions()
    {
        return $this->field_type === 'select' && !empty($this->options);
    }
}

