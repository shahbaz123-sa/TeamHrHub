import { hasPermission } from "@/utils/permission";

const emailRouteComponent = () => import("@/pages/apps/email/index.vue");

// 👉 Redirects
export const redirects = [
  // ℹ️ We are redirecting to different pages based on role.
  // NOTE: Role is just for UI purposes. ACL is based on abilities.
  {
    path: "/",
    name: "index",
    redirect: (to) => {
      // Check if user is logged in
      const userData = useCookie("userData");
      if (!userData.value || !userData.value.roles) {
        return { name: "login", query: to.query };
      }

      // Role-based redirects using hasRole function with explicit roles
      if (hasPermission("ceo_dashboard.read")) {
        return { name: "dashboards-hrm" };
      }

      if (hasPermission("hr_dashboard.read")) {
        return { name: "dashboards-hr" };
      }

      if (hasPermission("employee_dashboard.read")) {
        return { name: "dashboards-employee" };
      }

      // Fallback: If no specific role found, redirect to login
      return { name: "login", query: to.query };
    },
  },
  {
    path: "/pages/user-profile",
    name: "pages-user-profile",
    redirect: () => ({
      name: "pages-user-profile-tab",
      params: { tab: "profile" },
    }),
  },
  {
    path: "/pages/account-settings",
    name: "pages-account-settings",
    redirect: () => ({
      name: "pages-account-settings-tab",
      params: { tab: "account" },
    }),
  },
];
export const routes = [
  // Email filter
  {
    path: "/apps/email/filter/:filter",
    name: "apps-email-filter",
    component: emailRouteComponent,
    meta: {
      navActiveLink: "apps-email",
      layoutWrapperClasses: "layout-content-height-fixed",
    },
  },

  // Email label
  {
    path: "/apps/email/label/:label",
    name: "apps-email-label",
    component: emailRouteComponent,
    meta: {
      // contentClass: 'email-application',
      navActiveLink: "apps-email",
      layoutWrapperClasses: "layout-content-height-fixed",
    },
  },
  {
    path: "/dashboards/logistics",
    name: "dashboards-logistics",
    component: () => import("@/pages/apps/logistics/dashboard.vue"),
  },
  {
    path: "/dashboards/academy",
    name: "dashboards-academy",
    component: () => import("@/pages/apps/academy/dashboard.vue"),
  },
  {
    path: "/apps/ecommerce/dashboard",
    name: "apps-ecommerce-dashboard",
    component: () => import("@/pages/dashboards/ecommerce.vue"),
  },
  {
    path: "/apps/hrm/dashboard",
    name: "apps-hrm-dashboard",
    component: () => import("@/pages/dashboards/hrm.vue"),
  },
  {
    path: "/hrm/payroll/payslip",
    name: "hrm-payroll-payslip",
    component: () => import("@/pages/hrm/payroll/payslip.vue"),
  },
  {
    path: "/hrm/leave/balances",
    name: "hrm-leave-balances",
    component: () => import("@/pages/hrm/leave/balances/index.vue"),
    meta: { requiresAuth: true },
  },
  // User Profile Page
  {
    path: "/my-profile",
    name: "my-profile",
    component: () => import("@/pages/user-profile/index.vue"),
    meta: {
      requiresAuth: true,
    },
  },
  {
    path: "/503",
    name: "pages-misc-service-unavailable",
    component: () => import("@/pages/pages/misc/service-unavailable.vue"),
    meta: {
      layout: "blank",
      public: true,
    },
  },
];
