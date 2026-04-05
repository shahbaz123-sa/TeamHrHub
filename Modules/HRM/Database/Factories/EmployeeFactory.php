<?php

namespace Modules\HRM\Database\Factories;

use Modules\HRM\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $this->faker->locale('ur_PK');

        return [
            // Personal Details
            'name' => $this->faker->name,
            'father_name' => $this->faker->name('male'),
            'phone' => $this->faker->unique()->phoneNumber,
            'emergency_contact_name' => $this->faker->name,
            'emergency_contact_relation' => $this->faker->randomElement(['Father', 'Mother', 'Spouse', 'Brother', 'Friend']),
            'emergency_phone' => $this->faker->phoneNumber,
            'dob' => $this->faker->date(),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'cnic' => $this->faker->numerify('#####-#######-#'),
            'blood_group' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'marital_status' => $this->faker->randomElement(['single', 'married', 'divorced']),
            // 'employment_type' => $this->faker->randomElement(['full_time', 'part_time', 'contract']),
            // 'employment_status' => $this->faker->randomElement(['active', 'on_leave', 'terminated', 'suspended']),

            'personal_email' => $this->faker->unique()->safeEmail,
            'official_email' => $this->faker->unique()->companyEmail,
            'official_email_password' => 'password123',
            'status' => $this->faker->boolean,
            'address1' => $this->faker->address,
            'address2' => $this->faker->secondaryAddress,

            // Company Detail (Assume foreign keys exist)
            'employee_code' => 'E-' . rand(10000, 99999),
            'branch_id' => 1,
            'department_id' => 1,
            'designation_id' => 1,
            'date_of_joining' => $this->faker->date(),

            // Document uploads (null by default)
            'resume' => null,
            'experience_letter' => null,
            'salary_slip' => null,
            'photo' => null,
            'cnic_document' => null,
            'offer_letter' => null,
            'contract' => null,

            // Bank Details
            'account_holder_name' => $this->faker->name,
            'bank_name' => $this->faker->randomElement(['HBL', 'UBL', 'MCB', 'Alfalah', 'Meezan']),
            'account_number' => $this->faker->bankAccountNumber,
            'iban' => 'PK' . $this->faker->bankAccountNumber,
            'branch_location' => $this->faker->city,
        ];
    }
}
