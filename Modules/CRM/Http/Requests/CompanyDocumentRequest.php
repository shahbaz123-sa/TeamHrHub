<?php

namespace Modules\CRM\Http\Requests;

use Modules\Core\Rules\FileOrPathRule;
use Illuminate\Foundation\Http\FormRequest;

class CompanyDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user() && $this->user()->can('customer.update');
    }

    public function rules()
    {
        return [
            'document' => [
                'nullable',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg', 'pdf']),
                'max:100000'
            ],
            'document_type' => 'required|string|max:255',
        ];
    }
}
