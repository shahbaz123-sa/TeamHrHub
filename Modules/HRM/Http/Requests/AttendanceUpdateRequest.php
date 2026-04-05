<?php

namespace Modules\HRM\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_in' => 'nullable|date_format:H:i:s',
            'check_out' => 'nullable|date_format:H:i:s',
            'status' => 'required|string|in:present,holiday,absent,late,leave,half-leave,short-leave,not-marked',
            'note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'check_in.date_format' => 'Check-in time must be in HH:MM:SS format.',
            'check_out.date_format' => 'Check-out time must be in HH:MM:SS format.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be one of: present, absent.',
            'note.max' => 'Note cannot exceed 1000 characters.',
        ];
    }
}
