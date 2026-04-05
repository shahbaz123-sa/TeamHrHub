<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDependentRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('dependent.update');
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'relation' => 'sometimes|string|max:100',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'age' => 'nullable|integer|min:0'
        ];
    }
}
