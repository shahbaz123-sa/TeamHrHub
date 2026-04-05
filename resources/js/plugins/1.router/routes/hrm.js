import { hasOnlyRole, hasPermission } from "@/utils/permission";

const routes = [
  {
    name: "dashboards-ceo",
    canAccess: () => hasPermission("ceo_dashboard.read"),
  },
  {
    name: "dashboards-hrm",
    canAccess: () => hasPermission("ceo_dashboard.read"),
  },
  {
    name: "dashboards-hr",
    canAccess: () => hasPermission("hr_dashboard.read"),
  },
  {
    name: "dashboards-employee",
    canAccess: () => hasPermission("employee_dashboard.read"),
  },
  {
    name: "hrm-employee-details-id",
    canAccess: () => hasPermission("employee.read"),
  },
  {
    name: "hrm-employee-edit-id",
    canAccess: () => hasPermission("employee.update"),
  },
  {
    name: "hrm-employee-list",
    canAccess: () => !hasOnlyRole("Employee") && hasPermission("employee.read"),
  },
  {
    name: "hrm-employee-add",
    canAccess: () =>
      !hasOnlyRole("Employee") && hasPermission("employee.create"),
  },
    {
        name: "hrm-employee-assign-role",
        canAccess: () => hasPermission("employee.create"),
    },
    {
        name: "hrm-users-login-users",
        canAccess: () => hasPermission("logged_in_users.read"),
    },
  {
    name: "hrm-leave-list",
    canAccess: () => hasPermission("leave.read"),
  },
  {
    name: "hrm-leave-balances",
    canAccess: () => hasPermission("leave.read"),
  },
  {
    name: "hrm-attendance-list",
    canAccess: () => hasPermission("attendance.read"),
  },
  {
    name: "hrm-attendance-add",
    canAccess: () => hasPermission("attendance.create"),
  },
  {
    name: "hrm-asset-list",
    canAccess: () => hasPermission("asset.read"),
  },
  {
    name: "hrm-asset-assignment-history",
    canAccess: () => hasPermission("asset_assignment_history.read"),
  },
  {
    name: "hrm-ticket-list",
    canAccess: () => hasPermission("ticket.read"),
  },
  {
    name: "hrm-company-policy-list",
    canAccess: () => hasPermission("company_policy.read"),
  },
  {
    name: "hrm-payroll",
    canAccess: () => hasPermission("employee_salary.read"),
  },
  {
    name: "hrm-payroll-payslip",
    canAccess: () => hasPermission("payroll_generation.read"),
  },
  {
    name: "hrm-payroll-set-salary-id",
    canAccess: () => hasPermission("employee_salary.read"),
  },
  {
    name: "hrm-payroll-salary-generation",
    canAccess: () => hasPermission("payroll_generation.read"),
  },
  {
    name: "hrm-payroll-tax-slab",
    canAccess: () => hasPermission("tax_slab.read"),
  },
  {
    name: "hrm-branch-list",
    canAccess: () => hasPermission("branch.read"),
  },
  {
    name: "hrm-department-list",
    canAccess: () => hasPermission("department.read"),
  },
  {
    name: "hrm-designation-list",
    canAccess: () => hasPermission("designation.read"),
  },
  {
    name: "hrm-leave-type-list",
    canAccess: () => hasPermission("leave_type.read"),
  },
  {
    name: "hrm-document-type-list",
    canAccess: () => hasPermission("document_type.read"),
  },
  {
    name: "hrm-payslip-type-list",
    canAccess: () => hasPermission("payslip_type.read"),
  },
    {
        name: "hrm-allowance-list",
        canAccess: () => hasPermission("allowance_option.read"),
    },
    {
        name: "hrm-employee-rules-list",
        canAccess: () => true,
    },
  {
    name: "hrm-loan-option-list",
    canAccess: () => hasPermission("loan_option.read"),
  },
  {
    name: "hrm-deduction-list",
    canAccess: () => hasPermission("deduction_option.read"),
  },
  {
    name: "hrm-goal-type-list",
    canAccess: () => hasPermission("goal_type.read"),
  },
  {
    name: "hrm-training-type-list",
    canAccess: () => hasPermission("training_type.read"),
  },
  {
    name: "hrm-award-type-list",
    canAccess: () => hasPermission("award_type.read"),
  },
  {
    name: "hrm-employment-status-list",
    canAccess: () => hasPermission("employment_status.read"),
  },
  {
    name: "hrm-employment-type-list",
    canAccess: () => hasPermission("employment_type.read"),
  },
  {
    name: "hrm-termination-type-list",
    canAccess: () => hasPermission("termination_type.read"),
  },
  {
    name: "hrm-job-category-list",
    canAccess: () => hasPermission("job_category.read"),
  },
  {
    name: "hrm-job-stage-list",
    canAccess: () => hasPermission("job_stage.read"),
  },
  {
    name: "hrm-performance-type-list",
    canAccess: () => hasPermission("performance_type.read"),
  },
  {
    name: "hrm-competency-list",
    canAccess: () => hasPermission("competency.read"),
  },
  {
    name: "hrm-expense-type-list",
    canAccess: () => hasPermission("expense_type.read"),
  },
  {
    name: "hrm-ticket-category-list",
    canAccess: () => hasPermission("ticket_category.read"),
  },
  {
    name: "hrm-asset-type-list",
    canAccess: () => hasPermission("asset_type.read"),
  },
  {
    name: "hrm-asset-attribute-list",
    canAccess: () => hasPermission("asset_attributes.read"),
  },
  {
    name: "hrm-holidays-list",
    canAccess: () => hasPermission("holidays.read"),
  },
    {
        name: "hrm-roles",
        canAccess: () => hasPermission("role.read"),
    },
    {
        name: "hrm-reports-attendance-weekly",
        canAccess: () => hasPermission("weekly_attendance_report.read"),
    },
    {
        name: "hrm-reports-attendance-monthly",
        canAccess: () => hasPermission("monthly_attendance_report.read"),
    },
    {
        name: "hrm-reports-attendance",
        canAccess: () => hasPermission("attendance_summary_report.read"),
    },
    {
        name: "hrm-reports-attendance-attendance-summary",
        canAccess: () => hasPermission("employee_attendance_status_report.read"),
    },
  {
    name: "my-profile",
    canAccess: () => true,
  },
  {
    name: "hrm-payroll-deductions",
    canAccess: () => hasPermission("branch.read"),
  },
];

export default routes;
