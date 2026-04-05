<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTrainingTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('training_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:training_types,name,{$id}" : "unique:training_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('training_type.update');
    }
}
