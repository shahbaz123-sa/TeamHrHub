<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AssetAssignmentHistoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'asset_id' => $this->asset_id,
            'employee_id' => $this->employee_id,
            'assigned_date' => optional($this->assigned_date)->toDateString(),
            'returned_at' => optional($this->returned_at)->toDateTimeString(),
            'created_at' => optional($this->created_at)->toDateTimeString(),
            'unassigned_date' => optional($this->unassigned_date)->toDateString(),
            'asset' => $this->whenLoaded('asset', function () {
                return [
                    'id' => $this->asset?->id,
                    'name' => $this->asset?->name,
                    'serial_no' => $this->asset?->serial_no,
                ];
            }),
            'employee' => $this->whenLoaded('employee', function () {
                return [
                    'id' => $this->employee?->id,
                    'name' => $this->employee?->name,
                    'profile_picture' => $this->employee?->user?->avatar_url,
                    'official_email' => $this->employee?->official_email,
                    'personal_email' => $this->employee?->personal_email,
                ];
            }),
        ];
    }
}
