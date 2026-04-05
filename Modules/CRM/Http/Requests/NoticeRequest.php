<?php

namespace Modules\CRM\Http\Requests;

use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class NoticeRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('notice.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('notice.update');
        }
        return $user && $user->can('notice.read');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'year' => 'required|integer',
            'type_id' => 'required|integer|exists:crm.notice_types,id',
            'pdf_attachment' => [
                'nullable',
                new FileOrPathRule(['pdf']),
                'max:500000'
            ],
            'excel_attachment' => [
                'nullable',
                new FileOrPathRule(['xls', 'xlsx', 'csv']),
                'max:500000'
            ],
            'is_active' => 'boolean',
        ];
    }
}
