<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAllowanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('allowance_option.update');
    }

    public function rules(): array
    {
        $id = $this->route('allowance') ?? $this->route('id');
        $uniqueRule = $id ? "unique:allowances,name,{$id}" : "unique:allowances,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }
}
