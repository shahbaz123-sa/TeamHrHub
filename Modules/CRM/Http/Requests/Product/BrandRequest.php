<?php

namespace Modules\CRM\Http\Requests\Product;

use Modules\Core\Rules\SlugRegexRule;
use Modules\Core\Rules\FileOrPathRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class BrandRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('product_brand.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('product_brand.update');
        }
        return $user && $user->can('product_brand.read');
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
                'unique:crm.product_brands,slug,' . $this->route('brand', 0)
            ],
            'image' => [
                'nullable',
                new FileOrPathRule(['jpeg', 'png', 'jpg', 'webp', 'svg']),
                'max:1024'
            ],
            'is_active' => 'boolean',
        ];
    }
}
