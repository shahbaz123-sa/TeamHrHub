<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLeaveTypeRequest extends FormRequest
{
    public function rules(): array
    {
        return [  
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quota' => 'required|numeric',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('leave_type.update');
    }
}
