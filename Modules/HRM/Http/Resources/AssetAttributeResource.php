<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetAttributeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'field_type' => $this->field_type,
            'options' => $this->options,
            'formatted_options' => $this->formatted_options,
            'has_options' => $this->hasOptions(),
            'asset_type_id' => $this->asset_type_id,
            'asset_type_name' => $this->whenLoaded('assetType', function () {
                return $this->assetType->name;
            }),
            'asset_type' => $this->whenLoaded('assetType', function () {
                return [
                    'id' => $this->assetType->id,
                    'name' => $this->assetType->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

