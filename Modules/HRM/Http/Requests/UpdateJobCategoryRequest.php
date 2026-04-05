<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobCategoryRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('job_category') ?? $this->route('id');
        $uniqueRule = $id ? "unique:job_categories,name,{$id}" : "unique:job_categories,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('job_category.update');
    }
}
