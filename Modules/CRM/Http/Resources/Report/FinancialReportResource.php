<?php

namespace Modules\CRM\Http\Resources\Report;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class FinancialReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'report_date' => $this->report_date,
            'press_release' => $this->press_release ? Storage::disk('s3')->url($this->press_release) : null,
            'financial_report' => $this->financial_report ? Storage::disk('s3')->url($this->financial_report) : null,
            'presentation' => $this->presentation ? Storage::disk('s3')->url($this->presentation) : null,
            'transcript' => $this->transcript ? Storage::disk('s3')->url($this->transcript) : null,
            'video' => $this->video ? Storage::disk('s3')->url($this->video) : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
