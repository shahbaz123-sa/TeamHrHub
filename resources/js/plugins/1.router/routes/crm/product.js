import { hasPermission } from "@/utils/permission";
import attributeRoutes from "./product/attribute";

const routes = [
  {
    name: "crm-product-add",
    showInNav: false,
    canAccess: () => hasPermission("product.create"),
  },
  {
    name: "crm-product-edit-id",
    showInNav: false,
    canAccess: () => hasPermission("product.update"),
  },
  {
    name: "crm-product-details-id",
    showInNav: false,
    canAccess: () => hasPermission("product.read"),
  },
  {
    title: "Product List",
    name: "crm-product-list",
    to: "crm-product-list",
    canAccess: () => hasPermission("product.read"),
  },
  ...attributeRoutes,
  {
    title: "Categories",
    name: "crm-product-category-list",
    to: "crm-product-category-list",
    canAccess: () => hasPermission("product_category.read"),
  },
  {
    title: "Tag",
    name: "crm-product-tag-list",
    to: "crm-product-tag-list",
    canAccess: () => hasPermission("product_tag.read"),
  },
  {
    title: "Unit of measurement",
    name: "crm-product-uom-list",
    to: "crm-product-uom-list",
    canAccess: () => hasPermission("product_uom.read"),
  },
  {
    title: "Brand",
    name: "crm-product-brand-list",
    to: "crm-product-brand-list",
    canAccess: () => hasPermission("product_brand.read"),
  },
  {
    title: "City",
    name: "crm-product-city-list",
    to: "crm-product-city-list",
    canAccess: () => hasPermission("product_city.read"),
  },
];

export default routes;
