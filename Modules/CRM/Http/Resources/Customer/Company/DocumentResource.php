<?php

namespace Modules\CRM\Http\Resources\Customer\Company;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class DocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'document_type' => $this->document_type,
            'document_url' => $this->document_url ? Storage::disk('s3')->url($this->document_url) : '',
        ];
    }
}
