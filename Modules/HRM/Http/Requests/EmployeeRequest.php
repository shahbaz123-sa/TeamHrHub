<?php

namespace Modules\HRM\Http\Requests;

use Modules\HRM\Rules\FileOrPath;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class EmployeeRequest extends FormRequest
{
    public function authorize()
    {
        $user = $this->user();
        if ($this->isMethod('POST')) {
            return $user && $user->can('employee.create');
        }
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return $user && $user->can('employee.update');
        }
        return $user && $user->can('employee.read');
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()->all(),
            ], 422)
        );
    }

    protected function prepareForValidation()
    {
        if ($this->input('termination_type_id') === null) {
            $this->merge([
                'termination_type_id'        => null,
                'termination_reason'         => null,
                'termination_date'           => null,
                'termination_effective_date' => null,
            ]);
        }
        if (is_string($this->attendance_days)) {
            $this->merge([
                'attendance_days' => json_decode($this->attendance_days, true),
            ]);
        }
        
        if (is_string($this->assigned_devices)) {
            $this->merge([
                'assigned_devices' => json_decode($this->assigned_devices, true),
            ]);
        }
    }

    public function rules()
    {
        $rules = [
            // Personal Details
            'name' => 'required|string|max:255',
            'father_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:employees,phone',
            'emergency_contact_name' => 'required|string|max:255',
            'emergency_contact_relation' => 'required|string|max:255',
            'emergency_phone' => 'required|string|max:20',
            'dob' => 'required|date',
            'gender' => 'required|in:male,female,other',
            'cnic' => 'required|string|max:15|unique:employees,cnic',
            'blood_group' => 'required|string|max:10',
            'marital_status' => 'required|in:single,married,divorced,widowed',
            'dependents' => 'nullable|integer|min:0',
            'employment_type_id' => 'required|exists:employment_types,id',
            'employment_status_id' => 'required|exists:employment_statuses,id',
            'status' => 'required',
            'personal_email' => 'required|email|unique:employees,personal_email',
            'official_email' => 'required|email|unique:employees,official_email',
            'official_email_password' => 'nullable|string|min:8',
            'address1' => 'required|string',
            'address2' => 'nullable|string',

            // Company Details
            'employee_code' => 'required|string|max:255|unique:employees,employee_code|regex:/^[A-Za-z0-9-]+$/',
            'branch_id' => 'required|exists:branches,id',
            'designation_id' => 'required|exists:designations,id',
            'department_id' => 'required|exists:departments,id',
            'date_of_joining' => 'required|date',
            'reporting_to' => 'nullable|exists:employees,id',
            'bonus' => 'nullable|numeric|min:0',
            'termination_type_id' => 'nullable|exists:termination_types,id',

            'termination_reason' => [
                function ($attribute, $value, $fail) {
                    $term = $this->input('termination_type_id', null);
                    if (!is_null($term) && $term !== '') {
                        if (empty($value)) {
                            $fail('The termination reason field is required when a termination type is provided.');
                        }
                    }
                },
                'nullable',
                'string',
                'max:500'
            ],
            'termination_date' => [
                function ($attribute, $value, $fail) {
                    $term = $this->input('termination_type_id', null);
                    if (!is_null($term) && $term !== '') {
                        if (empty($value)) {
                            $fail('The termination date field is required when a termination type is provided.');
                        }
                    }
                },
                'nullable',
                'date'
            ],
            'termination_effective_date' => [
                function ($attribute, $value, $fail) {
                    $term = $this->input('termination_type_id', null);
                    if (!is_null($term) && $term !== '') {
                        if (empty($value)) {
                            $fail('The termination effective date field is required when a termination type is provided.');
                        }
                    }
                },
                'nullable',
                'date'
            ],
            // 'job_category_id' => 'required|exists:job_categories,id',
            // 'job_stage_id' => 'required|exists:job_stages,id',

            // Document fields
            'photo' => ['nullable', new FileOrPath(['jpeg', 'png', 'jpg']), 'max:10240'],
            'cnic_document' => ['nullable', new FileOrPath(['jpeg', 'png', 'jpg', 'pdf']), 'max:10240'],
            'resume' => ['nullable', new FileOrPath(['pdf']), 'max:10240'],
            'experience_letter' => ['nullable', new FileOrPath(['pdf']), 'max:10240'],
            'offer_letter' => ['nullable', new FileOrPath(['pdf']), 'max:10240'],
            'contract' => ['nullable', new FileOrPath(['pdf']), 'max:10240'],

            // Document fields
            'machine_name' => 'nullable|string|max:255',
            'system_processor' => 'nullable|string|max:255',
            'system_type' => 'nullable|string|max:255',
            'machine_password' => 'nullable|string|max:255',
            'installed_ram' => 'nullable|integer|min:0',
            'headphone' => 'nullable|in:"Yes","No"',
            'mouse' => 'nullable|in:"Yes","No"',
            'laptop_charger' => 'nullable|in:"Yes","No"',

            // Bank Details
            'account_holder_name' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'iban' => 'nullable|string|max:255',
            'branch_location' => 'required|string|max:255',

            // Attendance Days
            'attendance_days' => 'required|array',
            'attendance_days.*.day' => 'required|string|in:Monday,Tuesday,Wednesday,Thursday,Friday,Saturday,Sunday',
            'attendance_days.*.inside_office' => 'boolean',
            'attendance_days.*.outside_office' => 'boolean',
            'attendance_days.*.allow_late_checkin' => 'boolean',
            'attendance_days.*' => function ($attribute, $value, $fail) {
                if ( $value['is_working_day'] && isset($value['inside_office'], $value['outside_office']) &&
                    !$value['inside_office'] && !$value['outside_office']
                ) {
                    $fail("Either inside_office or outside_office must be true for {$value['day']}.");
                }
            },

            'attendance_days.*.checkin_time' => [
                'required_if:attendance_days.*.is_working_day,true',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            'attendance_days.*.checkout_time' => [
                'required_if:attendance_days.*.is_working_day,true',
                'nullable',
                'regex:/^\d{2}:\d{2}(:\d{2})?$/',
            ],

            // Dynamic Documents
            'dynamic_documents' => 'nullable|array',
            'dynamic_documents.*.file' => ['nullable', new FileOrPath(['pdf', 'jpg', 'jpeg', 'png']), 'max:10240'],
            'dynamic_documents.*.document_type_id' => 'required_with:dynamic_documents|exists:document_types,id',
            'dynamic_documents.*.document_type_name' => 'nullable|string|max:255',
            'dynamic_documents.*.existing_document_id' => 'nullable|integer|exists:employee_documents,id',

            // Assigned Devices
            'assigned_devices' => 'nullable|array',
            'assigned_devices.*.asset_id' => 'required_with:assigned_devices|exists:assets,id',
            'assigned_devices.*.assigned_date' => 'required_with:assigned_devices|date',
        ];

        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $employeeId = $this->route('employee')->id;

            $rules['phone'] .= ',' . $employeeId;
            $rules['cnic'] .= ',' . $employeeId;
            $rules['personal_email'] .= ',' . $employeeId;
            $rules['official_email'] .= ',' . $employeeId;

            $rules['photo'] = ['nullable', new FileOrPath(['jpeg', 'png', 'jpg']), 'max:10240'];
            $rules['cnic_document'] = ['nullable', new FileOrPath(['jpeg', 'png', 'jpg', 'pdf']), 'max:10240'];

            // Dynamic Documents for update - same as create but with nullable files
            $rules['dynamic_documents'] = 'nullable|array';
            $rules['dynamic_documents.*.file'] = ['nullable', new FileOrPath(['pdf', 'jpg', 'jpeg', 'png']), 'max:10240'];
            $rules['dynamic_documents.*.document_type_id'] = 'required_with:dynamic_documents|exists:document_types,id';
            $rules['dynamic_documents.*.document_type_name'] = 'nullable|string|max:255';
            $rules['dynamic_documents.*.existing_document_id'] = 'nullable|integer|exists:employee_documents,id';

            // Assigned Devices for update
            $rules['assigned_devices'] = 'nullable|array';
            $rules['assigned_devices.*.asset_id'] = 'required_with:assigned_devices|exists:assets,id';
            $rules['assigned_devices.*.assigned_date'] = 'required_with:assigned_devices|date';

            unset($rules['employee_code']);
        }

        return $rules;
    }

    public function messages()
    {
        return [
            'resume.mimes' => 'The resume must be a PDF, DOC, or DOCX file',
            'photo.max' => 'The photo must not exceed 2MB',
            'cnic_document.max' => 'The CNIC document must not exceed 2MB',
            'phone.unique' => 'This phone number is already registered',
            'cnic.unique' => 'This CNIC is already registered',
            'personal_email.unique' => 'This personal email is already registered',
            'official_email.unique' => 'This official email is already registered',
        ];
    }
}




