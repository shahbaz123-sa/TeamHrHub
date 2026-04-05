<?php

namespace Modules\CRM\Http\Requests\Product\Attribute;

use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class ValueRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_attribute_value.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_attribute_value.update');
        }
        return $user && $user->can('product_attribute_value.read');
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                new SlugRegexRule(),
                'unique:crm.product_attribute_values,slug,' . $this->route('value', 0)
            ],
            'attribute_id' => 'required|exists:crm.product_attributes,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
