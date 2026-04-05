<?php

namespace Modules\CRM\Http\Resources\Order;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'company_id' => $this->company_id,
            'document_type' => $this->document_type,
            'document_url' => $this->document_url ? Storage::disk('s3')->url($this->document_url) : '',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
