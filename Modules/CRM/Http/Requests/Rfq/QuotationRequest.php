<?php

namespace Modules\CRM\Http\Requests\Rfq;

use Modules\CRM\Http\Requests\BaseFormRequest;

class QuotationRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('rfq.update');
    }

    public function rules()
    {
        return [
            'procs' => 'required|string',
            'due_date' => 'required|date',
            'price_per_unit' => 'required|numeric',
            'total_price' => 'nullable|numeric',
            'invoice' => 'nullable|file',
        ];
    }

    public function messages()
    {
        return [
            'procs.required' => 'Quotation title field is required',
            'procs.string' => 'Quotation title field must be valid string',
        ];
    }
}
