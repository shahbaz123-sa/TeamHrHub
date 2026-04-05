<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('deduction_option.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:deductions,name',
            'description' => 'nullable|string',
        ];
    }
}
