<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompetencyRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('competency') ?? $this->route('id');
        $uniqueRule = $id ? "unique:competencies,name,{$id}" : "unique:competencies,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('competency.update');
    }
}
