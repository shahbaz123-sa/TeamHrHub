<?php

namespace Modules\CRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'order_name' => $this->unique_order_id,
            'order_type' => $this->order_type,
            'customer_id' => $this->user_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => Order\ItemResource::collection($this->whenLoaded('items')),
            'histories' => Order\HistoryResource::collection($this->whenLoaded('histories')),
            'documents' => Order\DocumentResource::collection($this->whenLoaded('documents')),
            'rfq_id' => $this->rfq_id,
            'rfq' => $this->whenLoaded('rfq', [
                'id' => $this->rfq?->id,
                'reference_no' => $this->rfq?->reference_no,
            ]),
            'company_id' => $this->company_id,
            'status' => $this->status,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'shipping_fee' => $this->shipping_fee,
            'total_amount' => number_format($this->total_amount, '2'),
            'state' => $this->state,
            'city' => $this->city,
            'postcode' => $this->postcode,
            'street_address' => $this->street_address,
            'shipping_address' => "{$this->street_address}, {$this->city}, {$this->state}, {$this->postcode}",
            'shipping_option' => $this->shipping_option,
            'platfoam' => $this->platfoam,
            'site_contact_person' => $this->site_contact_person,
            'site_contact_phone' => $this->site_contact_phone,
            'special_instructions' => $this->special_instructions,
            'payment_schedule' => $this->payment_schedule,
            'purchase_order_number' => $this->purchase_order_number,
            'finance_contact' => $this->finance_contact,
            'cancel_reason' => $this->cancel_reason,
            'order_date' => Carbon::parse($this->createdAt)->format('D, M d, Y'),
            'order_datetime' => Carbon::parse($this->createdAt)->format('D, M d, Y \a\t g:i A'),
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
