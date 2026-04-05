import { hasPermission } from "@/utils/permission";

const routes = [
  {
    title: "Notice List",
    name: "crm-notice-list",
    to: "crm-notice-list",
    canAccess: () => hasPermission("notice.read"),
  },
  {
    title: "Notice Types",
    name: "crm-notice-type-list",
    to: "crm-notice-type-list",
    canAccess: () => hasPermission("notice_type.read"),
  },
];

export default routes;
