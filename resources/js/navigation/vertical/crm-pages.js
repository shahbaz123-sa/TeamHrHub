import crmNoticesRoute from "@/plugins/1.router/routes/crm/notice";
import crmPricesRoute from "@/plugins/1.router/routes/crm/price";
import crmProductsRoute from "@/plugins/1.router/routes/crm/product";
import crmReportsRoute from "@/plugins/1.router/routes/crm/report";
import crmPostsRoute from "@/plugins/1.router/routes/crm/post";
import { hasPermission } from "@/utils/permission";

const crmChildren = [];
const crmMenu = [];

const filterMenu = (routes) => {
  return routes
    .map((route) => {
      if (!(route.showInNav ?? true)) {
        return null;
      }

      if (route.canAccess !== undefined && route.canAccess()) {
        return route;
      }

      if (route.children !== undefined) {
        const filteredChildren = route.children.filter(
          (child) => typeof child.canAccess === "function" && child.canAccess()
        );

        if (filteredChildren.length > 0) {
          return {
            ...route,
            children: filteredChildren,
          };
        }
      }

      return null;
    })
    .filter((item) => item !== null);
};

const productChildren = filterMenu(crmProductsRoute);
const reportChildren = filterMenu(crmReportsRoute);
const noticeChildren = filterMenu(crmNoticesRoute);
const priceChildren = filterMenu(crmPricesRoute);
const postsRoute = filterMenu(crmPostsRoute);

if (hasPermission("product.read") && productChildren.length) {
  crmChildren.push({
    title: "Product",
    children: productChildren,
  });
}

if (reportChildren.length) {
  crmChildren.push({
    title: "Report",
    children: reportChildren,
  });
}

if (noticeChildren.length) {
  crmChildren.push({
    title: "Notice",
    children: noticeChildren,
  });
}

if (priceChildren.length) {
  crmChildren.push({
    title: "Price",
    children: priceChildren,
  });
}

if (hasPermission("post.read")) {
  crmChildren.push({
    title: "Posts",
    children: postsRoute,
  });
}

if (hasPermission("customer.read")) {
  crmChildren.push({
    title: "Customers",
    to: { name: "crm-customer-list" },
  });
}

if (hasPermission("order.read")) {
  crmChildren.push({
    title: "Orders",
    to: { name: "crm-order-list" },
  });
}

if (hasPermission("rfq.read")) {
  crmChildren.push({
    title: "RFQ",
    to: { name: "crm-rfq-list" },
  });
}

if (hasPermission("credit_application.read")) {
  crmChildren.push({
    title: "Credit Requests",
    to: { name: "crm-credit-application-list" },
  });
}

if (hasPermission("supplier.read")) {
    crmChildren.push({
        title: "Suppliers",
        to: { name: "crm-supplier-list" },
    });
}

if (hasPermission("email_setting.read")) {
  crmChildren.push({
    title: "Email Settings",
    to: { name: "crm-email-settings-list" },
  });
}

if (crmChildren.length) {
  crmMenu.push({
    title: "Ecommerce",
    icon: { icon: "tabler-shopping-cart" },
    children: crmChildren,
  });
}

export default crmMenu;
