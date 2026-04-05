import noticeRoutes from "./crm/notice";
import postRoutes from "./crm/post";
import priceRoutes from "./crm/price";
import productRoutes from "./crm/product";
import reportRoutes from "./crm/report";

const routes = [
  ...productRoutes,
  ...reportRoutes,
  ...noticeRoutes,
  ...priceRoutes,
  ...postRoutes,
  {
    name: "crm-customer-list",
    showInNav: false,
    canAccess: () => hasPermission("customer.read"),
  },
  {
    name: "crm-customer-details-id",
    showInNav: false,
    canAccess: () => hasPermission("customer.read"),
  },
  {
    name: "crm-order-list",
    showInNav: false,
    canAccess: () => hasPermission("order.read"),
  },
  {
    name: "crm-order-details-id",
    showInNav: false,
    canAccess: () => hasPermission("order.read"),
  },
  {
    name: "crm-rfq-list",
    showInNav: false,
    canAccess: () => hasPermission("rfq.read"),
  },
  {
    name: "crm-rfq-details-id",
    showInNav: false,
    canAccess: () => hasPermission("rfq.read"),
  },
  {
    name: "crm-rfq-form-submission-details-id",
    showInNav: false,
    canAccess: () => hasPermission("rfq.read"),
  },
  {
    name: "crm-credit-application-list",
    showInNav: false,
    canAccess: () => hasPermission("credit_application.read"),
  },
  {
    name: "crm-credit-application-form-submission-details-id",
    showInNav: false,
    canAccess: () => hasPermission("credit_application.read"),
  },
  {
    name: "crm-company-id-credit-application",
    showInNav: false,
    canAccess: () => hasPermission("credit_application.update"),
  },
  {
    name: "crm-email-settings-list",
    showInNav: false,
    canAccess: () => hasPermission("email_setting.read"),
  },
  {
    name: "crm-supplier-list",
    showInNav: false,
    canAccess: () => hasPermission("supplier.read"),
  },
  {
    name: "crm-supplier-details-id",
    showInNav: false,
    canAccess: () => hasPermission("supplier.read"),
  },
];
export default routes;
