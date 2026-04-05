<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePerformanceTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('performance_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:performance_types,name,{$id}" : "unique:performance_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('performance_type.update');
    }
}
