<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAllowanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('allowance_option.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:allowances,name',
            'description' => 'nullable|string',
        ];
    }
}
