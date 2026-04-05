import { hasOnlyRole, hasPermission } from "@/utils/permission";

const menu = [{ heading: "Main Pages" }];

const hrmDashboards = [
  {
    title: "CEO Dashboard",
    to: "dashboards-hrm",
    permission: "ceo_dashboard.read",
  },
  {
    title: "HR Dashboard",
    to: "dashboards-hr",
    permission: "hr_dashboard.read",
  },
  {
    title: "Employee Dashboard",
    to: "dashboards-employee",
    permission: "employee_dashboard.read",
  },
  {
    title: "Reports",
    permission: "reports.read",
    children: [
        { title: "Employees Attendance", to: "hrm-reports-attendance", permission: "attendance_summary_report.read" },
        { title: "Attendance Summary", to: "hrm-reports-attendance-attendance-summary", permission: "employee_attendance_status_report.read" },
        { title: "Weekly Attendance", to: "hrm-reports-attendance-weekly", permission: "weekly_attendance_report.read" },
        { title: "Monthly Attendance", to: "hrm-reports-attendance-monthly", permission: "monthly_attendance_report.read" },
    ],
  },
];

const filteredHrmDashboards = hrmDashboards.filter(
  (item) => item.permission && hasPermission(item.permission)
);

if (filteredHrmDashboards.length > 0) {
  menu.push({
    title: "HRM",
    icon: { icon: "tabler-smart-home" },
    children: filteredHrmDashboards,
  });
}

if (hasOnlyRole("Employee")) {
  menu.push({
    title: "Employee",
    icon: { icon: "tabler-users" },
    to: {
      name: "hrm-employee-details-id",
      params: { id: useCookie("userData").value?.employee_id },
    },
  });
} else if (!hasOnlyRole("Employee") && hasPermission("employee.read")) {
  menu.push({
    title: "Employee",
    icon: { icon: "tabler-users" },
    to: "hrm-employee-list",
  });
}

if (hasPermission("leave.read") || hasPermission("attendance.read")) {
  menu.push({
    title: "Timesheet",
    icon: { icon: "tabler-calendar-up" },
    children: [
      hasPermission("leave.read")
        ? { title: "Leaves", to: "hrm-leave-list" }
        : null,
      hasPermission("leave.read")
        ? { title: "Leave Balances", to: "hrm-leave-balances" }
        : null,
      hasPermission("attendance.read")
        ? { title: "Attendance", to: "hrm-attendance-list" }
        : null,
    ].filter(Boolean),
  });
}

const assetsChildren = [
  { title: "Assets List", to: "hrm-asset-list", permission: "asset.read" },
  { title: "Asset Types", to: "hrm-asset-type-list", permission: "asset_type.read" },
];

assetsChildren.push({
    title: "Assets History",
    to: "hrm-asset-assignment-history",
    permission: "asset_assignment_history.read",
});

assetsChildren.push({
    title: "Asset Attributes",
    to: "hrm-asset-attribute-list",
    permission: "asset_attributes.read",
});

  menu.push({
    title: "Assets",
    icon: { icon: "tabler-device-laptop" },
    permission: "asset.read",
    children: assetsChildren,
    to: { name: "hrm-asset-list" },
  });

if (hasPermission("ticket.read")) {
  menu.push({
    title: "Tickets",
    icon: { icon: "tabler-ticket" },
    to: "hrm-ticket-list",
  });
}

if (hasPermission("company_policy.read")) {
  menu.push({
    title: "About Zarea",
    icon: { icon: "tabler-layout-sidebar-inactive" },
    to: "hrm-company-policy-list",
  });
}

const payrollChildren = [];

if (hasPermission("employee_salary.read")) {
    payrollChildren.push({ title: "Salary & Compensation", to: "hrm-payroll" });
}
if (hasPermission("payroll_generation.read")) {
    payrollChildren.push({ title: "Salary Generation", to: "hrm-payroll-salary-generation" });
}
if (hasPermission("tax_slab.read")) {
    payrollChildren.push({ title: "Tax Slabs", to: "hrm-payroll-tax-slab" });
}
if (hasPermission("payroll_generation.read") && false) {
  payrollChildren.push({
    title: "Payslip",
    to: "hrm-payroll-payslip",
  });
}

if (payrollChildren.length) {
  menu.push({
    title: "Payroll",
    icon: { icon: "tabler-calendar-dollar" },
    children: payrollChildren,
  });
}

const hrAdminSetupChildren = [
    { title: "Branches", to: "hrm-branch-list", permission: "branch.read" },
    { title: "Payroll Deductions", to: "hrm-payroll-deductions", permission: "branch.read"},
  {
    title: "Departments",
    to: "hrm-department-list",
    permission: "department.read",
  },
  {
    title: "Designations",
    to: "hrm-designation-list",
    permission: "designation.read",
  },
  {
    title: "Leave Types",
    to: "hrm-leave-type-list",
    permission: "leave_type.read",
  },
    { title: "Employee Rules", to: "hrm-employee-rules-list", permission: "employee_rules.read" },
  {
    title: "Document Types",
    to: "hrm-document-type-list",
    permission: "document_type.read",
  },
  {
    title: "Payslip Types",
    to: "hrm-payslip-type-list",
    permission: "payslip_type.read",
  },
  {
    title: "Allowance Options",
    to: "hrm-allowance-list",
    permission: "allowance_option.read",
  },
  {
    title: "Loan Options",
    to: "hrm-loan-option-list",
    permission: "loan_option.read",
  },
  {
    title: "Deduction Options",
    to: "hrm-deduction-list",
    permission: "deduction_option.read",
  },
  {
    title: "Goal Types",
    to: "hrm-goal-type-list",
    permission: "goal_type.read",
  },
  {
    title: "Training Types",
    to: "hrm-training-type-list",
    permission: "training_type.read",
  },
  {
    title: "Award Types",
    to: "hrm-award-type-list",
    permission: "award_type.read",
  },
  {
    title: "Employment Status",
    to: "hrm-employment-status-list",
    permission: "employment_status.read",
  },
  {
    title: "Employment Type",
    to: "hrm-employment-type-list",
    permission: "employment_type.read",
  },
  {
    title: "Termination Type",
    to: "hrm-termination-type-list",
    permission: "termination_type.read",
  },
  {
    title: "Job Categories",
    to: "hrm-job-category-list",
    permission: "job_category.read",
  },
  {
    title: "Job Stages",
    to: "hrm-job-stage-list",
    permission: "job_stage.read",
  },
  {
    title: "Performance Types",
    to: "hrm-performance-type-list",
    permission: "performance_type.read",
  },
  {
    title: "Competencies",
    to: "hrm-competency-list",
    permission: "competency.read",
  },
  {
    title: "Expense Types",
    to: "hrm-expense-type-list",
    permission: "expense_type.read",
  },
  {
    title: "Ticket Category",
    to: "hrm-ticket-category-list",
    permission: "ticket_category.read",
  },
  {
    title: "Holidays",
    to: "hrm-holidays-list",
    permission: "holidays.read",
  },
];

const filteredHrAdminSetupChildren = hrAdminSetupChildren.filter(
  (item) => item.permission && hasPermission(item.permission)
);
if (filteredHrAdminSetupChildren.length) {
  menu.push({
    title: "HR Administration",
    icon: { icon: "tabler-database" },
    children: filteredHrAdminSetupChildren,
  });
}

const rolesChildren = [
  {
    title: "Create Role",
    to: "hrm-roles",
    permission: "role.read",
  },
    {
        title: "Assign Roles",
        to: "hrm-employee-assign-role",
        permission: "employee.update",
    },
    {
        title: "Logged-In Users",
        to: "hrm-users-login-users",
        permission: "logged_in_users.read",
    },
];

const filteredRolesChildren = rolesChildren.filter(
    (item) => item.permission && hasPermission(item.permission)
);
if (filteredRolesChildren.length) {
  menu.push({
    title: "Access Management",
    icon: { icon: "tabler-lock" },
    children: filteredRolesChildren,
  });
}
// if (hasPermission("role.read")) {
//   menu.push({
//     title: "Roles",
//     icon: { icon: "tabler-lock" },
//     to: "hrm-roles",
//   });
// }

export default menu;
