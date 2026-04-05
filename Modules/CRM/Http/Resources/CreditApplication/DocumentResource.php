<?php

namespace Modules\CRM\Http\Resources\CreditApplication;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'credit_application_id' => $this->credit_application_id,
            'document_type' => $this->document_type,
            'document_url' => $this->document_url ? Storage::disk('s3')->url($this->document_url) : '',
            'file_name' => $this->file_name,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'company_id' => $this->company_id,
            'uploaded_at' => Carbon::parse($this->created_at)->format('d M Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
