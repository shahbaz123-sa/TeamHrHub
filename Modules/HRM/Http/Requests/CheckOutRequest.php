<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Modules\HRM\Helpers\AttendanceHelper;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;

class CheckOutRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('attendance.create');
    }

    public function rules()
    {
        return [
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'from_office' => 'nullable|boolean',
            'location' => 'nullable|string|max:500',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $employee = auth()->user()->employee;

            if (!$employee) {
                $validator->errors()->add('longitude', 'No employee record found for this user');
                return;
            }

            $attendaceDay = $employee->attendanceDays()->where('day', now()->format('l'))->first();
            if (!$attendaceDay) {
                $validator->errors()->add('longitude', 'Your inside/outside office attendance days is not defined yet');
                return;
            }

            $branch = $employee->branch;
            if (!$branch) {
                $validator->errors()->add('longitude', 'Branch is not attached with this employee');
                return;
            }

            if (!$branch->allow_remote && !$attendaceDay->outside_office && $attendaceDay->inside_office) {
                $lat = $this->input('latitude');
                $lng = $this->input('longitude');
                $accuracy = $this->input('accuracy', 0);

                if (!AttendanceHelper::isNearbyOffice($lat, $lng, $accuracy, $branch)) {
                    $validator->errors()->add('location', 'Youre out of the office premises.');
                }
            }
        });
    }
}
