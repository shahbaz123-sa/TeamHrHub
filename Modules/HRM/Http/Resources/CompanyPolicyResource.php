<?php

namespace Modules\HRM\Http\Resources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyPolicyResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'branch_id' => $this->branch_id,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'display_order' => $this->display_order,
            'title' => $this->title,
            'description' => $this->description,
            'attachment_path' => $this->attachment_path,
            'attachment_url' => $this->attachment_path ? Storage::disk('s3')->url($this->attachment_path) : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
