<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceImportRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('attendance.create');
    }

    public function rules()
    {
        return [
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv'],
            'override' => ['nullable', 'boolean'],
        ];
    }
}
