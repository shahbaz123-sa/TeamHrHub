import { hasPermission } from "@/utils/permission";

const routes = [
  {
    showInNav: false,
    title: "Latest Report",
    name: "crm-report-latest-report-list",
    to: "crm-report-latest-report-list",
    canAccess: () => hasPermission("latest_report.read"),
  },
  {
    title: "Financial Report",
    name: "crm-report-financial-report-list",
    to: "crm-report-financial-report-list",
    canAccess: () => hasPermission("financial_report.read"),
  },
];

export default routes;
