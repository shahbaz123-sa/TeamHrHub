<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\CRM\Http\Resources\Customer\CompanyResource;

class RfqResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'customer_name' => $this->whenLoaded('user', function () {
                if ($this->user->type === 'B2B') {
                    return $this->whenLoaded('company', $this->company->company_name);
                }

                return $this->user->profile->full_name ?? $this->user->username;
            }),
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', new CompanyResource($this->company)),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', new CustomerResource($this->user)),
            'item' => $this->whenLoaded('item', new Rfq\ItemResource($this->item)),
            'quotation' => $this->whenLoaded('quotation', new Rfq\QuotationResource($this->quotation)),
            'reference_no' => $this->reference_no,
            'status' => $this->status,
            'status_label' => Str::headline(Str::lower($this->status)),
            'delivery_location' => $this->delivery_location,
            'preferred_delivery_date' => $this->preferred_delivery_date ? Carbon::parse($this->preferred_delivery_date)->format('d M Y') : '',
            'req_date' => $this->created_at->format('d M Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'supporting_documents' => $this->supporting_documents ? Storage::disk('s3')->url($this->supporting_documents) : '',
            'unique_rfq_id' => $this->unique_rfq_id,
            'payment_method' => $this->payment_method,
            'assigned_to' => $this->assigned_to,
        ];
    }
}
