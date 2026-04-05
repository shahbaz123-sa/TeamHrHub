<?php

namespace Modules\HRM\Http\Resources;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray($request)
    {
        $forEdit = $request->input('for-edit', false);
        $forRoleAssignment = $request->routeIs('hrm.employee.with-roles');
        $dob = filled($this->dob)
            ? ($forEdit
                ? $this->dob->toDateString()
                : $this->dob->format('M d, Y'))
            : '';

        $doj = filled($this->date_of_joining)
            ? ($forEdit
                ? $this->date_of_joining->toDateString()
                : $this->date_of_joining->format('M d, Y'))
            : '';

        return [
            'id' => $this->id,

            // Personal Details
            'name' => $this->name,
            'father_name' => $this->father_name,
            'phone' => $this->phone,
            'profile_picture' => $this->user?->avatar_url ?? null,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_relation' => $this->emergency_contact_relation,
            'emergency_phone' => $this->emergency_phone,
            'dob' => $dob,
            'gender' => $this->gender,
            'cnic' => $this->cnic,
            'blood_group' => $this->blood_group,
            'marital_status' => $this->marital_status,
            'dependents' => $this->dependents,
            'personal_email' => $this->personal_email,
            'official_email' => $this->official_email,
            'official_email_password' => $this->official_email_password,
            'address1' => $this->address1,
            'address2' => $this->address2,

            // Company Details
            'branch_id' => $this->branch_id,
            'employee_code' => $this->employee_code,
            'designation_id' => $this->designation_id,
            'department_id' => $this->department_id,
            'date_of_joining' => $doj,
            'reporting_to' => $forEdit ? $this->reporting_to : $this->whenLoaded('reportingTo'),
            'bonus' => $this->bonus,
            'employment_type_id' => $this->employment_type_id,
            'employment_status_id' => $this->employment_status_id,
            'employment_type' => $this->whenLoaded('employmentType') ?? $this->employment_type,
            'employment_status' => $this->whenLoaded('employmentStatus') ?? $this->employment_status,
            'status' => $this->status,

            // Document URLs
            'photo_url' => $this->photo ? Storage::disk('s3')->url($this->photo) : null,
            'cnic_document_url' => $this->cnic_document ? Storage::disk('s3')->url($this->cnic_document) : null,
            'resume_url' => $this->resume ? Storage::disk('s3')->url($this->resume) : null,
            'experience_letter_url' => $this->experience_letter ? Storage::disk('s3')->url($this->experience_letter) : null,
            'salary_slip_url' => $this->salary_slip ? Storage::disk('s3')->url($this->salary_slip) : null,
            'offer_letter_url' => $this->offer_letter ? Storage::disk('s3')->url($this->offer_letter) : null,
            'contract_url' => $this->contract ? Storage::disk('s3')->url($this->contract) : null,

            // Device Details
            'machine_name' => $this->machine_name,
            'system_processor' => $this->system_processor,
            'system_type' => $this->system_type,
            'headphone' => $this->headphone,
            'machine_password' => $this->machine_password,
            'installed_ram' => $this->installed_ram,
            'mouse' => $this->mouse,
            'laptop_charger' => $this->laptop_charger,

            // Account Details
            'account_holder_name' => $this->account_holder_name,
            'bank_name' => $this->bank_name,
            'account_number' => $this->account_number,
            'iban' => $this->iban,
            'branch_location' => $this->branch_location,

            // Dynamic Documents
            'documents' => $this->whenLoaded('documents', function () {
                return $this->documents
                    ->sortBy(function ($document) {
                        // Default docs use their order
                        if ($document->documentType?->is_default) {
                            return $document->documentType->order;
                        }

                        // Non-default docs go after defaults
                        return PHP_INT_MAX;
                    })
                    ->values()->map(function ($document) {
                    return [
                        'id' => $document->id,
                        'document_type_id' => $document->document_type_id,
                        'document_type_name' => $document->documentType->name ?? 'Unknown',
                        'is_default' => $document->documentType->is_default ?? false,
                        'order' => $document->documentType->order ?? 0,
                        'file_url' => $document->file_url,
                        'file_path' => $document->file_path,
                    ];
                });
            }),

            // Relationships
            'department' => $this->whenLoaded('department'),
            'designation' => $this->whenLoaded('designation'),
            'branch' => $this->whenLoaded('branch'),
            'user' => $this->whenLoaded('user'),
            'job_category_id' => $this->job_category_id,
            'job_stage_id' => $this->job_stage_id,
            'termination_type_id' => $this->termination_type_id,
            'termination_reason' => $this->termination_reason,
            'termination_date' => filled($this->termination_date)
                ? ($forEdit
                ? Carbon::parse($this->termination_date)->toDateString()
                : Carbon::parse($this->termination_date)->format('M d, Y'))
                : '',
            'termination_effective_date' => filled($this->termination_effective_date)
                ? ($forEdit
                    ? Carbon::parse($this->termination_effective_date)->toDateString()
                    : Carbon::parse($this->termination_effective_date)->format('M d, Y'))
                : '',
            'job_category' => $this->whenLoaded('jobCategory'),
            'job_stage' => $this->whenLoaded('jobStage'),
            'termination_type' => $this->whenLoaded('terminationType'),
            'assets' => $this->whenLoaded('assets', function () {
                return $this->assets->map(function ($asset) {
                    return [
                        'id' => $asset->id,
                        'name' => $asset->name,
                        'serial_no' => $asset->serial_no,
                        'purchase_date' => $asset->purchase_date?->format('Y-m-d'),
                        'description' => $asset->description,
                        'asset_type' => $asset->assetType ? [
                            'id' => $asset->assetType->id,
                            'name' => $asset->assetType->name,
                        ] : null,
                        'pivot' => [
                            'assigned_date' => $asset->pivot->assigned_date,
                        ],
                    ];
                });
            }),

            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            'roles' => $forRoleAssignment ? $this->user?->roles->map(fn($r) => ['id' => $r->id, 'name' => $r->name])->values() : '',

            // Attendance days
            'attendance_days' => $this->attendanceDays
                ? $this->attendanceDays
                    ->sortBy(fn($d) => [
                        'Monday'    => 1,
                        'Tuesday'   => 2,
                        'Wednesday' => 3,
                        'Thursday'  => 4,
                        'Friday'    => 5,
                        'Saturday'  => 6,
                        'Sunday'    => 7,
                    ][$d->day] ?? 99)
                    ->values()
                    ->map(function ($setting) {
                        return [
                            'day' => $setting->day,
                            'is_working_day' => (bool) $setting->is_working_day,
                            'inside_office' => (bool) $setting->inside_office,
                            'outside_office' => (bool) $setting->outside_office,
                            'checkin_time' => substr($setting->checkin_time, 0, 5),
                            'checkout_time' => substr($setting->checkout_time, 0, 5),
                            'allow_late_checkin' => (bool) $setting->allow_late_checkin,
                        ];
                    })
                : [],
        ];
    }
}
