<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentTypeRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('document_type') ?? $this->route('id');
        $uniqueRule = $id ? "unique:document_types,name,{$id}" : "unique:document_types,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
            'is_default' => 'sometimes',
            'order' => 'sometimes',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('document_type.update');
    }
}
