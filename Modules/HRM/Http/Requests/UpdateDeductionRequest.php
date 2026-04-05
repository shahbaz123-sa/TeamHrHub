<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDeductionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('deduction_option.update');
    }

    public function rules(): array
    {
        $id = $this->route('deduction') ?? $this->route('id');
        $uniqueRule = $id ? "unique:deductions,name,{$id}" : "unique:deductions,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }
}
