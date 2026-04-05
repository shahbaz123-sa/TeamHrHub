<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBranchRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        return $user && $user->can('branch.update');
    }

    public function rules()
    {
        return [
            'name' => 'sometimes|string|max:255',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'grace_period' => 'nullable|numeric',
            'attendance_radius' => 'nullable|numeric',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'office_start_time' => 'nullable|date_format:H:i',
            'office_end_time' => 'nullable|date_format:H:i',
            'allow_remote' => 'nullable|boolean',
            'time_deviations' => 'nullable|array',
            'time_deviations.*.day' => 'required|string',
            'time_deviations.*.check_in_deviation' => 'required|numeric',
            'time_deviations.*.check_out_deviation' => 'required|numeric',
        ];
    }
}
