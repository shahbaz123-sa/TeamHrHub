<?php

namespace Modules\CRM\Http\Requests\Product;

use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class UnitOfMeasurementRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_uom.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_uom.update');
        }
        return $user && $user->can('product_uom.read');
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
                'unique:crm.product_uoms,slug,' . $this->route('uom', 0)
            ],
            'is_active' => 'boolean',
        ];
    }
}
