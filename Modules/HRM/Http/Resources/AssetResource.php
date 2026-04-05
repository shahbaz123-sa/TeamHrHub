<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HRM\Models\Employee;

class AssetResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'asset_type_id' => $this->asset_type_id,
            'name' => $this->name,
            'serial_no' => $this->serial_no,
            'purchase_date' => $this->purchase_date?->format('Y-m-d'),
            'make_model' => $this->make_model,
            'description' => $this->description,
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee->id,
                    'name' => $this->employee->name,
                    'employee_code' => $this->employee->employee_code,
                    'profile_picture' => $this->employee->user?->avatar_url ?? null,
                ];
            }),
            'asset_type' => $this->whenLoaded('assetType', function () {
                return [
                    'id' => $this->assetType->id,
                    'name' => $this->assetType->name,
                ];
            }),
            'attributes' => $this->whenLoaded('attributeValues', function () {
                $attributes = new \stdClass();
                foreach ($this->attributeValues as $attrValue) {
                    $attributes->{$attrValue->attribute_id} = $attrValue->value;
                }
                return $attributes;
            }),
            'has_assignment_history' => $this->whenLoaded('assignmentHistories', function () {
                return $this->assignmentHistories->isNotEmpty();
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
