// HRM Module Constants

// Allowance Types
export const AllowanceType = {
  Fixed: 1,
  Percentage: 2,
  Variable: 3,
}

export const AllowanceTypeLabels = {
  [AllowanceType.Fixed]: 'Fixed',
  [AllowanceType.Percentage]: 'Percentage',
  [AllowanceType.Variable]: 'Variable',
}

export const AllowanceTypeOptions = [
  { title: 'Fixed', value: AllowanceType.Fixed },
  // { title: 'Percentage', value: AllowanceType.Percentage },
  // { title: 'Variable', value: AllowanceType.Variable },
]

// Employee Status
export const EmployeeStatus = {
  Active: 1,
  Inactive: 2,
  Terminated: 3,
  OnLeave: 4,
}

export const EmployeeStatusLabels = {
  [EmployeeStatus.Active]: 'Active',
  [EmployeeStatus.Inactive]: 'Inactive',
  [EmployeeStatus.Terminated]: 'Terminated',
  [EmployeeStatus.OnLeave]: 'On Leave',
}

// Employment Types
export const EmploymentType = {
  FullTime: 1,
  PartTime: 2,
  Contract: 3,
  Intern: 4,
}

export const EmploymentTypeLabels = {
  [EmploymentType.FullTime]: 'Full Time',
  [EmploymentType.PartTime]: 'Part Time',
  [EmploymentType.Contract]: 'Contract',
  [EmploymentType.Intern]: 'Intern',
}

// Gender
export const Gender = {
  Male: 1,
  Female: 2,
  Other: 3,
}

export const GenderLabels = {
  [Gender.Male]: 'Male',
  [Gender.Female]: 'Female',
  [Gender.Other]: 'Other',
}

// Marital Status
export const MaritalStatus = {
  Single: 1,
  Married: 2,
  Divorced: 3,
  Widowed: 4,
}

export const MaritalStatusLabels = {
  [MaritalStatus.Single]: 'Single',
  [MaritalStatus.Married]: 'Married',
  [MaritalStatus.Divorced]: 'Divorced',
  [MaritalStatus.Widowed]: 'Widowed',
}

// Blood Groups
export const BloodGroup = {
  APositive: 1,
  ANegative: 2,
  BPositive: 3,
  BNegative: 4,
  ABPositive: 5,
  ABNegative: 6,
  OPositive: 7,
  ONegative: 8,
}

export const BloodGroupLabels = {
  [BloodGroup.APositive]: 'A+',
  [BloodGroup.ANegative]: 'A-',
  [BloodGroup.BPositive]: 'B+',
  [BloodGroup.BNegative]: 'B-',
  [BloodGroup.ABPositive]: 'AB+',
  [BloodGroup.ABNegative]: 'AB-',
  [BloodGroup.OPositive]: 'O+',
  [BloodGroup.ONegative]: 'O-',
}

// Leave Types
export const LeaveType = {
  Annual: 1,
  Sick: 2,
  Personal: 3,
  Maternity: 4,
  Paternity: 5,
  Emergency: 6,
}

export const LeaveTypeLabels = {
  [LeaveType.Annual]: 'Annual Leave',
  [LeaveType.Sick]: 'Sick Leave',
  [LeaveType.Personal]: 'Personal Leave',
  [LeaveType.Maternity]: 'Maternity Leave',
  [LeaveType.Paternity]: 'Paternity Leave',
  [LeaveType.Emergency]: 'Emergency Leave',
}

// Leave Status
export const LeaveStatus = {
  Pending: 1,
  Approved: 2,
  Rejected: 3,
  Cancelled: 4,
}

export const LeaveStatusLabels = {
  [LeaveStatus.Pending]: 'Pending',
  [LeaveStatus.Approved]: 'Approved',
  [LeaveStatus.Rejected]: 'Rejected',
  [LeaveStatus.Cancelled]: 'Cancelled',
}

// Salary Status
export const SalaryStatus = {
  Active: 1,
  Inactive: 2,
}

export const SalaryStatusLabels = {
  [SalaryStatus.Active]: 'Active',
  [SalaryStatus.Inactive]: 'Inactive',
}
