<?php

namespace Modules\HRM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Asset extends Model
{
    use HasFactory;

    protected $fillable = [
        'asset_type_id',
        'name',
        'serial_no',
        'purchase_date',
        'make_model',
        'description',
    ];

    protected $casts = [
        'purchase_date' => 'date',
    ];

    public function assetType()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'assign_to');
    }

    public function attributeValues()
    {
        return $this->hasMany(AssetAttributeValue::class);
    }

    public function assignmentHistories()
    {
        return $this->hasMany(AssetAssignmentHistory::class);
    }

    protected static function newFactory()
    {
        return \Modules\HRM\Database\Factories\AssetFactory::new();
    }
}
