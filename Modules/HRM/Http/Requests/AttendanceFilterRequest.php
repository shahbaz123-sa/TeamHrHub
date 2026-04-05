<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceFilterRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('attendance.read');
    }

    public function rules()
    {
        return [
            'employees' => ['nullable', 'exists:employees,id'],
            'departments' => ['nullable', 'exists:departments,id'],
            'designation_id' => ['nullable', 'exists:designations,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'employment_status_id' => ['nullable', 'exists:employment_statuses,id'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2000', 'max:2100'],
            'status' => ['nullable', 'string', 'in:present,absent,late,half_day,holiday,not-marked,leave,half-leave,short-leave,checkout'],
            'per_page' => ['nullable', 'integer', 'min:-1', 'max:100'],
            'date' => ['nullable', 'date'],
            'today' => ['nullable', 'boolean'],
            'searchQuery' => ['nullable', 'string'],
            'sortBy' => ['nullable', 'string'],
            'orderBy' => ['nullable', 'string'],
            'format' => ['nullable', 'string', 'in:pdf,excel'],
        ];
    }
}
