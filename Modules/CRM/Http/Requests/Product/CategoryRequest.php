<?php

namespace Modules\CRM\Http\Requests\Product;

use Modules\Core\Rules\SlugRegexRule;
use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class CategoryRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_category.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_category.update');
        }
        return $user && $user->can('product_category.read');
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
                'unique:crm.product_categories,slug,' . $this->route('category', 0)
            ],
            'image' => [
                'nullable',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg']),
                'max:1024'
            ],
            'parent_id' => 'nullable|exists:crm.product_categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];
    }
}
