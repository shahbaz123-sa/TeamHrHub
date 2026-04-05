<?php

namespace Modules\CRM\Http\Requests\Notice;

use Modules\CRM\Http\Requests\BaseFormRequest;

class TypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        if ($this->isMethod('post')) {
            return $this->user()->can('notice_type.create');
        }
        if ($this->isMethod('put') || $this->isMethod('patch')) {
            return $this->user()->can('notice_type.update');
        }

        return $this->user()->can('notice_type.read');
    }

    protected function prepareForValidation()
    {
        if (empty($this->order)) {
            $this->merge([
                'order' => 0,
            ]);
        }

        if (isset($this->is_active) && is_string($this->is_active)) {
            $this->merge([
                'is_active' => filter_var($this->is_active, FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'order' => ['sometimes'],
        ];
    }
}
