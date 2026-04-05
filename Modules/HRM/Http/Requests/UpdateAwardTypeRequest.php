<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAwardTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('award_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:award_types,name,{$id}" : "unique:award_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('award_type.update');
    }
}
