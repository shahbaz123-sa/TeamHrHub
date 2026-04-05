<?php

namespace Modules\CRM\Http\Requests\Post;

use Modules\Core\Rules\SlugRegexRule;
use Modules\CRM\Http\Requests\BaseFormRequest;

class CategoryRequest extends BaseFormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('post_category.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('post_category.update');
        }
        return $user && $user->can('post_category.read');
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => [
                'required',
                'string',
                'max:255',
                new SlugRegexRule(),
                'unique:crm.post_categories,slug,' . $this->route('category', 0)
            ],
            'is_active' => 'boolean',
        ];
    }
}
