<?php

namespace Modules\CRM\Http\Requests\Product;

use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class TagRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_tag.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_tag.update');
        }
        return $user && $user->can('product_tag.read');
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
                'unique:crm.product_tags,slug,' . $this->route('tag', 0)
            ],
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
