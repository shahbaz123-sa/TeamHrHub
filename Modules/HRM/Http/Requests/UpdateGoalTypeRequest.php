<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGoalTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('goal_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:goal_types,name,{$id}" : "unique:goal_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('goal_type.update');
    }
}
