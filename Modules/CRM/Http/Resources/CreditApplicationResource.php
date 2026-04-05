<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;
use Modules\Auth\Http\Resources\UserResource;

class CreditApplicationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'reference_no' => $this->credit_reference,
            'customer_name' => $this->whenLoaded('user', function () {
                if ($this->user->type === 'B2B') {
                    return $this->whenLoaded('company', $this->company->company_name);
                }

                return $this->user->profile->full_name ?? $this->user->username;
            }),
            'user_id' => $this->user_id,
            'user' => $this->whenLoaded('user', new CustomerResource($this->user)),
            'company_id' => $this->company_id,
            'company' => $this->whenLoaded('company', new Customer\CompanyResource($this->company)),
            'customer_id' => $this->customer_id,
            'customer' => $this->whenLoaded('customer', new CustomerResource($this->customer)),
            'requested_credit_limit' => $this->requested_credit_limit,
            'formatted_req_credit_limit' => number_format($this->requested_credit_limit, 2),
            'category_id' => $this->category_id,
            'category' => $this->whenLoaded('category', new Product\CategoryResource($this->category)),
            'business_type' => $this->business_type,
            'annual_revenue' => $this->annual_revenue,
            'purpose_of_credit' => $this->purpose_of_credit,
            'status' => $this->status,
            'status_label' => Str::headline(Str::lower($this->status)),
            'approved_credit_limit' => $this->approved_credit_limit,
            'formatted_app_credit_limit' => number_format($this->approved_credit_limit, 2),
            'used_credit_limit' => $this->used_credit_limit,
            'formatted_used_credit_limit' => number_format($this->used_credit_limit, 2),
            'rejection_reason' => $this->rejection_reason,
            'reviewed_by' => $this->reviewed_by,
            'reviewed_at' => $this->reviewed_at,
            'approved_at' => Carbon::parse($this->reviewed_at)->format('d M Y'),
            'rejected_at' => Carbon::parse($this->reviewed_at)->format('d M Y'),
            'processing_time' => $this->processing_time,
            'documents' => $this->whenLoaded('documents', CreditApplication\DocumentResource::collection($this->documents)),
            'date' => Carbon::parse($this->created_at)->format('d M Y'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
