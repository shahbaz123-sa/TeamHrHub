<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobStageRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('job_stage') ?? $this->route('id');
        $uniqueRule = $id ? "unique:job_stages,name,{$id}" : "unique:job_stages,name";
        
        return [
            'name' => "sometimes|required|string|max:255|{$uniqueRule}",
            'description' => 'nullable|string',
        ];
    }

    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->can('job_stage.update');
    }
}
