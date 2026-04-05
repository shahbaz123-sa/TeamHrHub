<?php

namespace Modules\HRM\Http\Requests\Leave;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateUpdateRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('leave.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('leave.update');
        }
        return $user && $user->can('leave.read');
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422)
        );
    }

    public function rules()
    {
        return [
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'leave_reason' => 'nullable|string',
            'duration_type' => 'required|integer|in:1,2,3', // 1=full_day, 2=half_day, 3=short_leave
            'days' => 'nullable|numeric|min:0', // Basic validation for days column
            'leave_attachment' => 'nullable|sometimes|file|max:5120',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (request()->routeIs('leaves.update')) {
                $leave = $this->route('leave');

                if ($leave->hr_status !== 'pending' || $leave->manager_status !== 'pending') {
                    $validator->errors()->add('leave', 'Cannot update approved/rejected leaves.');
                }
            } else {
                if (empty(auth()->user()->employee->id)) {
                    $validator->errors()->add('leave', 'Logged in user has no record in employees');
                }
            }

            // Validate duration type date constraints
            if ($this->start_date && $this->end_date && $this->duration_type) {
                $startDate = \Carbon\Carbon::parse($this->start_date);
                $endDate = \Carbon\Carbon::parse($this->end_date);
                $diffDays = $startDate->diffInDays($endDate) + 1; // +1 to include both dates

                switch ($this->duration_type) {
                    case 2: // Half Day - Can only apply for one date
                        if ($diffDays > 1) {
                            $validator->errors()->add('end_date', 'Half Day leave can only be applied for one date.');
                        }
                        break;
                    case 3: // Short Leave - Can only apply for one date
                        if ($diffDays > 1) {
                            $validator->errors()->add('end_date', 'Short Leave can only be applied for one date.');
                        }
                        break;
                    case 1: // Full Day - Can span multiple dates
                    default:
                        // No restriction for full day leave
                        break;
                }
            }
        });
    }
}
