import { hasPermission } from "@/utils/permission";

const routes = [
  {
    showInNav: false,
    name: "crm-product-graph-price-import",
    canAccess: () => hasPermission("product_graph_price.create"),
  },
  {
    title: "Graph Price Import",
    name: "crm-product-graph-price-list",
    to: "crm-product-graph-price-list",
    canAccess: () => hasPermission("product_graph_price.read"),
  },
  {
    title: "Daily Price Import",
    name: "crm-product-daily-price-list",
    to: "crm-product-daily-price-list",
    canAccess: () => hasPermission("product_daily_price.read"),
  },
  {
    showInNav: false,
    name: "crm-product-daily-price-import",
    canAccess: () => hasPermission("product_daily_price.create"),
  },
  {
    showInNav: false,
    name: "crm-product-daily-price-details-batch",
    canAccess: () => hasPermission("product_daily_price.read"),
  },
  {
    showInNav: false,
    name: "crm-product-daily-price-history-list",
    canAccess: () => hasPermission("product_daily_price.read"),
  },
];

export default routes;
