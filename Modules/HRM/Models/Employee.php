<?php

namespace Modules\HRM\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\HRM\Database\Factories\EmployeeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\HRM\Models\Employee\Attendance\EmployeeAttendanceDay;

class Employee extends Model
{
    use SoftDeletes, HasFactory;

    protected $fillable = [
        // Personal Details
        'name',
        'father_name',
        'phone',
        'emergency_contact_name',
        'emergency_contact_relation',
        'emergency_phone',
        'dob',
        'gender',
        'cnic',
        'blood_group',
        'marital_status',
        'dependents',
        'employment_type_id',
        'employment_status_id',
        'personal_email',
        'official_email',
        'official_email_password',
        'status',
        'address1',
        'address2',

        // Document paths
        'resume',
        'experience_letter',
        'salary_slip',
        'photo',
        'cnic_document',
        'offer_letter',
        'contract',

        // Company Details
        'employee_code',
        'branch_id',
        'department_id',
        'designation_id',
        'date_of_joining',
        'reporting_to',
        'bonus',

        // Additional HRM Module Relationships
        'user_id',
        'termination_type_id',
        'termination_reason',
        'termination_date',
        'termination_effective_date',
        'job_category_id',
        'job_stage_id',

        // Device Details
        'machine_name',
        'system_processor',
        'system_type',
        'machine_password',
        'installed_ram',
        'headphone',
        'mouse',
        'laptop_charger',

        // Bank Details
        'account_holder_name',
        'bank_name',
        'account_number',
        'iban',
        'branch_location',
    ];

    protected $casts = [
        'dob' => 'date',
        'date_of_joining' => 'date',
    ];

    public function loginActivities()
    {
        return $this->hasMany(\App\Models\LoginActivity::class, 'user_id', 'user_id');
    }

    public function latestLoginActivity()
    {
        return $this->hasOne(\App\Models\LoginActivity::class, 'user_id', 'user_id')->latestOfMany();
    }

    public function reportingTo()
    {
        return $this->belongsTo(Employee::class, 'reporting_to');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function assets()
    {
        return $this->belongsToMany(Asset::class, 'employee_asset')
            ->withPivot('assigned_date')
            ->withTimestamps();
    }

    public function assetAssignmentHistories()
    {
        return $this->hasMany(AssetAssignmentHistory::class);
    }

    // Add all the missing relationships
    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function documentType()
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function payslipType()
    {
        return $this->belongsTo(PayslipType::class);
    }


    public function allowance()
    {
        return $this->belongsTo(Allowance::class);
    }

    public function loanOption()
    {
        return $this->belongsTo(LoanOption::class);
    }

    public function deduction()
    {
        return $this->belongsTo(Deduction::class);
    }

    public function goalType()
    {
        return $this->belongsTo(GoalType::class);
    }

    public function trainingType()
    {
        return $this->belongsTo(TrainingType::class);
    }

    public function awardType()
    {
        return $this->belongsTo(AwardType::class);
    }

    public function terminationType()
    {
        return $this->belongsTo(TerminationType::class);
    }

    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class);
    }

    public function jobStage()
    {
        return $this->belongsTo(JobStage::class);
    }

    public function performanceType()
    {
        return $this->belongsTo(PerformanceType::class);
    }

    public function competency()
    {
        return $this->belongsTo(Competency::class);
    }

    public function expenseType()
    {
        return $this->belongsTo(ExpenseType::class);
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function documents()
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function loans()
    {
        return $this->hasMany(EmployeeLoan::class);
    }

    public function deductions()
    {
        return $this->hasMany(EmployeeDeduction::class);
    }

    public function allowances()
    {
        return $this->hasMany(EmployeeAllowance::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function employmentType()
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function employmentStatus()
    {
        return $this->belongsTo(EmploymentStatus::class);
    }

    public function salaries()
    {
        return $this->hasMany(EmployeeSalary::class);
    }

    public function currentSalary()
    {
        return $this->hasOne(EmployeeSalary::class)->where('status', 1)->latest('effective_date');
    }

    public function attendanceDays()
    {
        return $this->hasMany(EmployeeAttendanceDay::class);
    }

    public function exemption()
    {
        return $this->hasOne(EmployeeExemption::class, 'employee_id');
    }

    public function salaryHistories()
    {
        return $this->hasMany(EmployeeSalaryHistory::class);
    }

    protected static function newFactory()
    {
        return EmployeeFactory::new();
    }
}
