<?php

namespace Modules\CRM\Http\Resources;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class NoticeResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'year' => $this->year,
            'type_id' => $this->type_id,
            'type' => $this->whenLoaded('type', function () {
                return $this->type ? [
                    'id' => $this->type->id,
                    'title' => $this->type->title,
                ] : null;
            }),
            'pdf_attachment' => $this->pdf_attachment ? Storage::disk('s3')->url($this->pdf_attachment) : null,
            'excel_attachment' => $this->excel_attachment ? Storage::disk('s3')->url($this->excel_attachment) : null,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
