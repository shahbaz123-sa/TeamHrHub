import { hasPermission } from "@/utils/permission";

const attributeChildrenRoutes = [];

if (hasPermission("product_attribute.read")) {
  attributeChildrenRoutes.push({
    title: "Attribute List",
    name: "crm-product-attribute-list",
    to: "crm-product-attribute-list",
    canAccess: () => hasPermission("product_attribute.read"),
  });
}

if (hasPermission("product_attribute_value.read")) {
  attributeChildrenRoutes.push({
    title: "Attribute Value",
    name: "crm-product-attribute-value-list",
    to: "crm-product-attribute-value-list",
    canAccess: () => hasPermission("product_attribute_value.read"),
  });
}

const attributeRoutes = [];
if (attributeChildrenRoutes.length) {
  attributeRoutes.push({
    title: "Attribute",
    children: attributeChildrenRoutes,
  });
}

export default attributeRoutes;
