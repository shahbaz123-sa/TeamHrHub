<?php

namespace Modules\CRM\Http\Resources\Report;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class LatestReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'attachment' => $this->attachment ? Storage::disk('s3')->url($this->attachment) : null,
            'report_date' => $this->report_date,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
